<?php

namespace App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirManajer\Pages\Dashboard;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNuirSubmissions extends ListRecords
{
    protected static string $resource = NuirSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(Dashboard::getUrl(panel: 'nuir-manajer')),
        ];
    }
}
