<?php

namespace App\Filament\NuirManajer\Resources\NuirSettingResource\Pages;

use App\Filament\NuirManajer\Resources\NuirSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNuirSettings extends ListRecords
{
    protected static string $resource = NuirSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data'),
        ];
    }
}
