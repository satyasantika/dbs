<?php

namespace App\Filament\NuirManajer\Resources\GuideAllocationResource\Pages;

use App\Filament\NuirManajer\Resources\GuideAllocationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ImportGuideAllocations extends Page
{
    protected static string $resource = GuideAllocationResource::class;

    protected static string $view = 'filament.nuir-manajer.resources.guide-allocation-resource.pages.import-guide-allocations';

    protected static ?string $title = 'Import Banyak — Copy-Paste dari Spreadsheet';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('← Kembali ke Daftar')
                ->color('gray')
                ->url(GuideAllocationResource::getUrl('index')),
        ];
    }
}
