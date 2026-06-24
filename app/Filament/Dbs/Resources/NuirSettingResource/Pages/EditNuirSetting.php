<?php

namespace App\Filament\Dbs\Resources\NuirSettingResource\Pages;

use App\Filament\Dbs\Resources\NuirSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNuirSetting extends EditRecord
{
    protected static string $resource = NuirSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
