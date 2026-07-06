<?php

namespace App\Support;

use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\User;

class NuirSeatStatusPresenter
{
    /**
     * @return array{label: string, color: string}
     */
    public static function present(?string $status): array
    {
        return match ($status) {
            'accepted' => ['label' => 'Diterima', 'color' => 'success'],
            'rejected' => ['label' => 'Ditolak', 'color' => 'danger'],
            default => ['label' => 'Menunggu Respons', 'color' => 'warning'],
        };
    }

    /**
     * Status kursi yang lebih deskriptif untuk panel dosen: menjelaskan
     * apa yang sedang ditunggu, bukan sekadar pending/accepted/rejected.
     *
     * @return array{label: string, color: string}
     */
    public static function detailed(NuirProposal $proposal, User $guide): array
    {
        $status = match ($guide->id) {
            $proposal->guide1_id => $proposal->guide1_status,
            $proposal->guide2_id => $proposal->guide2_status,
            default => null,
        };

        if ($status === 'rejected') {
            return ['label' => 'Ditolak', 'color' => 'danger'];
        }

        if ($status === 'accepted') {
            $otherId = $guide->id === $proposal->guide1_id ? $proposal->guide2_id : $proposal->guide1_id;
            $otherStatus = $guide->id === $proposal->guide1_id ? $proposal->guide2_status : $proposal->guide1_status;

            if ($otherId !== null && $otherStatus === 'accepted') {
                return ['label' => 'Diterima', 'color' => 'success'];
            }

            if ($otherId === null) {
                return ['label' => 'Diterima — Menunggu Usulan Pasangan Pembimbing', 'color' => 'info'];
            }

            return ['label' => 'Diterima — Menunggu Penerimaan dari Pasangan Pembimbing', 'color' => 'info'];
        }

        if (! $proposal->submission?->isContentFinalForPembimbing()) {
            return ['label' => 'Menunggu Selesai Validasi Referensi', 'color' => 'gray'];
        }

        $pendingLabels = collect(NuirContentReview::FIELDS)
            ->reject(fn (string $field): bool => NuirContentReview::query()
                ->where('nuir_submission_id', $proposal->nuir_submission_id)
                ->where('user_id', $guide->id)
                ->where('field', $field)
                ->where('approved', true)
                ->exists())
            ->map(fn (string $field): string => NuirContentFieldPresenter::config($field)['label']);

        if ($pendingLabels->isNotEmpty()) {
            return [
                'label' => 'Menunggu Persetujuan Anda: '.$pendingLabels->implode('/'),
                'color' => 'warning',
            ];
        }

        return ['label' => 'Semua Elemen Disetujui — Klik Ikon Sinkron', 'color' => 'info'];
    }
}
