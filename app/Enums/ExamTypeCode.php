<?php

namespace App\Enums;

/**
 * Satu-satunya sumber kebenaran warna/ikon/label jenis ujian — dipakai di
 * semua kolom badge Filament & kartu publik Beranda supaya sempro/semhas/
 * sidang selalu tampil konsisten di seluruh aplikasi. Kunci pakai
 * exam_types.id (stabil & sudah dipakai luas di business logic), bukan
 * kolom exam_types.code (nilainya 'sempro'/'semhas'/'skripsi' — id=3
 * kodenya 'skripsi', bukan 'sidang', lihat database/seeders/ExamSeeder.php).
 */
enum ExamTypeCode: int
{
    case Sempro = 1;
    case Semhas = 2;
    case Sidang = 3;

    public function label(): string
    {
        return match ($this) {
            self::Sempro => 'Seminar Proposal',
            self::Semhas => 'Seminar Hasil',
            self::Sidang => 'Sidang Skripsi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sempro => 'warning',
            self::Semhas => 'info',
            self::Sidang => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Sempro => 'heroicon-o-document-magnifying-glass',
            self::Semhas => 'heroicon-o-presentation-chart-bar',
            self::Sidang => 'heroicon-o-academic-cap',
        };
    }

    /**
     * Padanan emoji dari icon() — dipakai di badge HTML tulisan tangan
     * (App\Filament\Informasi\Pages\Beranda) yang dirender lewat
     * TextColumn::html(), karena Filament men-strip tag <svg> lewat
     * Str::sanitizeHtml() (Symfony HtmlSanitizer, tidak meng-allowlist svg)
     * — heroicon() asli aman dipakai di ->icon() Filament biasa (lewat
     * komponen <x-filament::icon>, bukan lewat sanitizer ini), tapi tidak
     * bisa disisipkan sebagai string HTML mentah.
     */
    public function emoji(): string
    {
        return match ($this) {
            self::Sempro => '🔍',
            self::Semhas => '📊',
            self::Sidang => '🎓',
        };
    }
}
