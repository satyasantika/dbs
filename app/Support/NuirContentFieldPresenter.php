<?php

namespace App\Support;

use App\Models\NuirSetting;
use App\Models\NuirSubmission;

class NuirContentFieldPresenter
{
    /**
     * Static display metadata (label/icon/accent/hint) shared by every panel
     * that renders the Judul/Novelty/Urgency/Impact content cards.
     *
     * @return array{field: string, label: string, badge: ?string, icon: string, accent: string, hint: string}
     */
    public static function config(string $field): array
    {
        return match ($field) {
            'title' => [
                'field' => 'title',
                'label' => 'Judul',
                'badge' => null,
                'icon' => 'heroicon-o-bookmark',
                'accent' => 'primary',
                'hint' => 'Judul topik penelitian yang diajukan mahasiswa.',
            ],
            'novelty' => [
                'field' => 'novelty',
                'label' => 'Novelty',
                'badge' => 'N',
                'icon' => 'heroicon-o-light-bulb',
                'accent' => 'info',
                'hint' => 'Kebaruan penelitian dibanding studi sebelumnya.',
            ],
            'urgency' => [
                'field' => 'urgency',
                'label' => 'Urgency',
                'badge' => 'U',
                'icon' => 'heroicon-o-clock',
                'accent' => 'warning',
                'hint' => 'Urgensi dan keterdesakan permasalahan yang diteliti.',
            ],
            'impact' => [
                'field' => 'impact',
                'label' => 'Impact',
                'badge' => 'I',
                'icon' => 'heroicon-o-arrow-trending-up',
                'accent' => 'success',
                'hint' => 'Dampak dan manfaat yang diharapkan dari penelitian.',
            ],
            default => throw new \InvalidArgumentException("Field tidak dikenal: {$field}"),
        };
    }

    public static function wordCountDescription(NuirSubmission $record, string $field): string
    {
        $text = $record->{$field} ?? '';
        $words = NuirTextLimits::wordCount($text);

        if ($field === 'title') {
            return "{$words} kata dikirim";
        }

        $setting = NuirSetting::where('year_generation', $record->year_generation)->first();
        $min = $setting?->{"min_words_{$field}"};
        $max = $setting?->{"max_words_{$field}"};

        $meta = "{$words} kata dikirim";

        if ($min !== null && $max !== null) {
            $meta .= " · batas {$min}–{$max} kata";
        } elseif ($max !== null) {
            $meta .= " · maks. {$max} kata";
        } elseif ($min !== null) {
            $meta .= " · min. {$min} kata";
        }

        return $meta;
    }
}
