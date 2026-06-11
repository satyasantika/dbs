<?php

namespace App\Filament\Resources\GuideExaminerResource\Pages;

use App\Filament\Resources\GuideExaminerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuideExaminer extends EditRecord
{
    protected static string $resource = GuideExaminerResource::class;

    protected function getRedirectUrl(): string
    {
        return GuideExaminerResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => GuideExaminerResource::canDelete($this->getRecord())),
        ];
    }
}
