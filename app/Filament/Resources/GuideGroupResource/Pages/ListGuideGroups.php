<?php

namespace App\Filament\Resources\GuideGroupResource\Pages;

use App\Filament\Resources\GuideGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuideGroups extends ListRecords
{
    protected static string $resource = GuideGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
