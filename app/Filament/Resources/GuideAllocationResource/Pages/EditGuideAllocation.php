<?php

namespace App\Filament\Resources\GuideAllocationResource\Pages;

use App\Filament\Resources\GuideAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuideAllocation extends EditRecord
{
    protected static string $resource = GuideAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
