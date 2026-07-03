<?php

namespace App\Filament\Dosen\Resources\NuirSubmissionResource\Pages;

use App\Filament\Dosen\Resources\NuirSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNuirSubmissions extends ListRecords
{
    protected static string $resource = NuirSubmissionResource::class;

    public function getSubheading(): ?string
    {
        return 'Submission NUIR mahasiswa yang mengusulkan Anda sebagai calon pembimbing.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(route('home')),
        ];
    }
}
