<?php

namespace App\Support;

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
}
