<?php

namespace App\Filament\Dbs\Resources\NuirSettingResource\Pages;

use App\Filament\Dbs\Resources\NuirSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNuirSettings extends ListRecords
{
    protected static string $resource = NuirSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
