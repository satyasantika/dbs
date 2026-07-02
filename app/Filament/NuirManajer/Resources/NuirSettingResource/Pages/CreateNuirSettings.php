<?php

namespace App\Filament\NuirManajer\Resources\NuirSettingResource\Pages;

use App\Filament\NuirManajer\Resources\NuirSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNuirSettings extends CreateRecord
{
    protected static string $resource = NuirSettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
