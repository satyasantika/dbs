<?php

namespace App\Filament\Resources\SetScoringToExaminerResource\Pages;

use App\Filament\Resources\SetScoringToExaminerResource;
use Filament\Resources\Pages\ListRecords;

class ListSetScoringToExaminers extends ListRecords
{
    protected static string $resource = SetScoringToExaminerResource::class;

    public function getTitle(): string
    {
        return 'Jadwal Ujian Belum Diset ke Penguji';
    }

    public function getSubheading(): ?string
    {
        return 'Pilih satu atau beberapa jadwal (centang header untuk halaman ini, atau "Pilih semua" untuk seluruh data), lalu gunakan aksi bulk Set ke penguji. Pastikan salah satu penguji ditandai ★ sebagai ketua — edit jika ketua belum ditentukan.';
    }
}
