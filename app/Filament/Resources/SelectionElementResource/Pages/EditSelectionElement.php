<?php

namespace App\Filament\Resources\SelectionElementResource\Pages;

use App\Filament\Resources\SelectionElementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSelectionElement extends EditRecord
{
    protected static string $resource = SelectionElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
