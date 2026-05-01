<?php

namespace App\Filament\Resources\SelectionElementResource\Pages;

use App\Filament\Resources\SelectionElementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSelectionElements extends ListRecords
{
    protected static string $resource = SelectionElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
