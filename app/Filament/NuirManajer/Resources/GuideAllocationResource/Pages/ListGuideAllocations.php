<?php

namespace App\Filament\NuirManajer\Resources\GuideAllocationResource\Pages;

use App\Filament\NuirManajer\Resources\GuideAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuideAllocations extends ListRecords
{
    protected static string $resource = GuideAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pasteImport')
                ->label('Import Banyak')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->url(GuideAllocationResource::getUrl('import')),
            Actions\CreateAction::make(),
        ];
    }
}
