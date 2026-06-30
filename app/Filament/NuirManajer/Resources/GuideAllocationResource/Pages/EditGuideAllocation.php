<?php

namespace App\Filament\NuirManajer\Resources\GuideAllocationResource\Pages;

use App\Filament\NuirManajer\Resources\GuideAllocationResource;
use Filament\Resources\Pages\EditRecord;

class EditGuideAllocation extends EditRecord
{
    protected static string $resource = GuideAllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
