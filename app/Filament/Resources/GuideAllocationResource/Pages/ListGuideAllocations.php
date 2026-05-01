<?php

namespace App\Filament\Resources\GuideAllocationResource\Pages;

use App\Filament\Resources\GuideAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuideAllocations extends ListRecords
{
    protected static string $resource = GuideAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
