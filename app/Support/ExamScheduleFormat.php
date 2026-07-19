<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Format tanggal/jam/ruang ujian jadi satu baris ringkas (emoji + pemisah
 * "|", ruang selalu diberi prefix "Ruang") — dipakai bersama oleh
 * App\Filament\Informasi\Pages\Beranda (kartu publik "Jadwal Ujian
 * Mendatang") dan App\Filament\Resources\ExamRegistrationResource (kartu
 * admin + ExamRegistrationsByDateWidget yang reuse getCardColumns())
 * supaya formatnya selalu identik di kedua tempat, satu sumber kebenaran
 * (pola yang sama seperti App\Enums\ExamTypeCode).
 */
class ExamScheduleFormat
{
    public static function inlineHtml(?Carbon $examDate, ?string $examTime, ?string $room): string
    {
        $tanggal = $examDate?->translatedFormat('d M Y') ?? '—';
        $jam = $examTime ? Carbon::parse($examTime)->format('H:i') : '—';
        $ruang = $room ?: '—';

        $sep = '<span class="exam-waktu-sep">|</span>';

        return '<span class="exam-waktu-item"><span class="exam-waktu-icon">📅</span>'.e($tanggal).'</span>'
            .' '.$sep.' '
            .'<span class="exam-waktu-item"><span class="exam-waktu-icon">🕐</span>'.e($jam).'</span>'
            .' '.$sep.' '
            .'<span class="exam-waktu-item"><span class="exam-waktu-icon">📍</span>Ruang '.e($ruang).'</span>';
    }
}
