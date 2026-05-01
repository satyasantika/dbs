<?php

namespace App\Filament\Resources\SelectionStageResource\Pages;

use App\Filament\Resources\SelectionStageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSelectionStage extends EditRecord
{
    protected static string $resource = SelectionStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
