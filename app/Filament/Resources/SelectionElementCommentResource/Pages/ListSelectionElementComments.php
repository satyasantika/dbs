<?php

namespace App\Filament\Resources\SelectionElementCommentResource\Pages;

use App\Filament\Resources\SelectionElementCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSelectionElementComments extends ListRecords
{
    protected static string $resource = SelectionElementCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
