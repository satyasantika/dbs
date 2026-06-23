<?php

namespace App\Filament\Resources\GuideExaminerResource\Pages;

use App\Filament\Resources\GuideExaminerResource;
use App\Filament\Resources\GuideExaminerResource\Concerns\HasListTableStateUrl;
use App\Filament\Resources\GuideExaminerResource\Concerns\RemembersListTableState;
use App\Services\Examination\ExamRegistrationExaminerSync;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuideExaminer extends EditRecord
{
    use HasListTableStateUrl;
    use RemembersListTableState;

    protected static string $resource = GuideExaminerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(ExamRegistrationExaminerSync::class)
            ->resolveChiefIdForSaveFromSlots(
                app(ExamRegistrationExaminerSync::class)->slotsFromGuideExaminer($this->record),
                $data,
            );
    }

    protected function getRedirectUrl(): string
    {
        return $this->appendListTableStateToUrl(
            GuideExaminerResource::getUrl('index'),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => GuideExaminerResource::canDelete($this->getRecord())),
        ];
    }
}
