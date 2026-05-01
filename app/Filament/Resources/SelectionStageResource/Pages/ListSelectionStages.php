<?php

namespace App\Filament\Resources\SelectionStageResource\Pages;

use App\Filament\Resources\SelectionStageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSelectionStages extends ListRecords
{
    protected static string $resource = SelectionStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
