<?php

namespace App\Filament\Resources\GuideExaminerResource\Pages;

use App\Filament\Resources\GuideExaminerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuideExaminers extends ListRecords
{
    protected static string $resource = GuideExaminerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
