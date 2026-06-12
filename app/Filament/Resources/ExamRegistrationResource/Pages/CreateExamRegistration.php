<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use App\Services\Examination\ExamRegistrationExaminerSync;
use Filament\Resources\Pages\CreateRecord;

class CreateExamRegistration extends CreateRecord
{
    protected static string $resource = ExamRegistrationResource::class;

    protected function afterCreate(): void
    {
        app(ExamRegistrationExaminerSync::class)->syncFromRegistration($this->record->fresh());
    }
}
