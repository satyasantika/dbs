<?php

namespace App\Support;

use App\Models\ExamRegistration;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ExamRegistrationWhatsappLinks
{
    public static function phoneDigits(ExamRegistration $record): ?string
    {
        $phone = ltrim(preg_replace('/\D/', '', (string) $record->student?->phone), '0');

        return $phone !== '' ? $phone : null;
    }

    public static function simpleChatUrl(ExamRegistration $record): ?string
    {
        $phone = static::phoneDigits($record);

        return $phone ? 'https://wa.me/62' . $phone : null;
    }

    public static function inviteUrl(ExamRegistration $record): ?string
    {
        $phone = static::phoneDigits($record);

        return $phone
            ? 'https://api.whatsapp.com/send/?phone=62' . $phone . '&text=' . rawurlencode(static::inviteMessage($record))
            : null;
    }

    public static function ralatUrl(ExamRegistration $record): ?string
    {
        $phone = static::phoneDigits($record);

        return $phone
            ? 'https://api.whatsapp.com/send/?phone=62' . $phone . '&text=' . rawurlencode(static::ralatMessage($record))
            : null;
    }

    public static function inviteMessage(ExamRegistration $record): string
    {
        $record->loadMissing(['student', 'examtype']);

        $examType   = $record->examtype?->name ?? 'Ujian';
        $name       = static::properName($record->student?->name);
        $examLower  = mb_strtolower($examType);
        $mode       = static::examModeLabel($record);
        $date       = static::formatDateInvite($record);
        $time       = static::formatTime($record);

        $text = "*INFORMASI Pelaksanaan Ujian {$examType}*\n\n"
            . "Saudara *{$name}*,\n"
            . "Kami telah menjadwalkan Anda untuk melaksanakan *ujian {$examLower}* secara *{$mode}* "
            . "pada hari *{$date}* pukul *{$time} WIB*.\n"
            . "Detail jadwal ujian selengkapnya tautan berikut.\n"
            . static::defaultScheduleLink() . "\n\n"
            . "Sebelum mengumpulkan berkas ujian di meja penguji, kami sarankan Saudara melakukan konfirmasi "
            . "ke dosen penguji terlebih dahulu, apakah penguji memerlukan hasil cetakannya atau cukup dengan "
            . "file ujian yang sudah diupload saat registrasi sebelumnya.\n\n"
            . "Anda *wajib hadir* di kampus dari pukul 07.00 hingga waktu akhir sesuai jadwal. "
            . "Daftar hadir ujian (di jurusan) *WAJIB* ditandatangani sebelum memasuki ruang ujian.\n\n"
            . "Demikian informasi ini Kami sampaikan. Atas perhatian Anda, Kami ucapkan terima kasih.\n"
            . "(ttd.) *Kajur Pendidikan Matematika*";

        return $text . static::onlineAppendix($record);
    }

    public static function ralatMessage(ExamRegistration $record): string
    {
        $record->loadMissing(['student', 'examtype']);

        $examType = $record->examtype?->name ?? 'Ujian';
        $name     = static::properName($record->student?->name);
        $mode     = static::examModeLabel($record);
        $date     = static::formatDateRalat($record);
        $time     = static::formatTime($record);
        $schedule = static::scheduleLinkForRalat($record);

        $text = "*RALAT Jadwal Ujian {$examType}*\n\n"
            . "Saudara *{$name}*,\n"
            . "Kami informasikan bahwa jadwal ujian Anda telah diubah ke hari *{$date} pukul {$time} WIB* "
            . "dilaksanakan *secara {$mode}*, karena harus menyesuaikan kembali dengan jadwal para penguji.\n"
            . "Kepastian jadwal ujian selengkapnya dilampirkan pada tautan berikut.\n"
            . $schedule . "\n\n"
            . "Mohon segera menyesuaikan dengan perubahan jadwal ini.\n\n"
            . "Demikian informasi ini Kami sampaikan. Atas perhatian Anda, Kami ucapkan terima kasih.\n"
            . "(ttd.) *Kajur Pendidikan Matematika*";

        return $text . static::onlineAppendix($record);
    }

    protected static function properName(?string $name): string
    {
        return Str::title(mb_strtolower(trim($name ?? '')));
    }

    protected static function examModeLabel(ExamRegistration $record): string
    {
        return filled($record->online_link)
            ? 'DARING (link ujian di bawah pesan ini)'
            : 'LURING';
    }

    protected static function formatDateInvite(ExamRegistration $record): string
    {
        if (!$record->exam_date) {
            return '—';
        }

        return $record->exam_date->locale('id')->isoFormat('dddd (D MMMM YYYY)');
    }

    protected static function formatDateRalat(ExamRegistration $record): string
    {
        if (!$record->exam_date) {
            return '—';
        }

        return $record->exam_date->locale('id')->isoFormat('dddd, D MMMM YYYY');
    }

    protected static function formatTime(ExamRegistration $record): string
    {
        if (!$record->exam_time) {
            return '—';
        }

        return Carbon::parse($record->exam_time)->format('H:i');
    }

    protected static function defaultScheduleLink(): string
    {
        return config('app.exam_schedule_link_default', 'https://supportfkip.unsil.ac.id/dbsmatematika');
    }

    protected static function scheduleLinkForRalat(ExamRegistration $record): string
    {
        if (filled($record->schedule_link)) {
            return trim((string) $record->schedule_link);
        }

        return static::defaultScheduleLink();
    }

    protected static function onlineAppendix(ExamRegistration $record): string
    {
        if (!filled($record->online_link)) {
            return '';
        }

        $parts = array_filter([
            filled($record->online_user) ? 'meeting id: ' . $record->online_user : null,
            filled($record->online_password) ? 'passcode: ' . $record->online_password : null,
            'link_zoom: ' . $record->online_link,
        ]);

        return $parts !== [] ? "\n" . implode("\n", $parts) : '';
    }
}
