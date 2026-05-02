<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use App\Models\ExamScore;
use App\Models\GuideExaminer;
use Filament\Resources\Pages\CreateRecord;

class CreateExamRegistration extends CreateRecord
{
    protected static string $resource = ExamRegistrationResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        $slots = [
            1 => $record->examiner1_id,
            2 => $record->examiner2_id,
            3 => $record->examiner3_id,
            4 => $record->guide1_id,
            5 => $record->guide2_id,
        ];

        foreach ($slots as $order => $userId) {
            if (!$userId) continue;

            ExamScore::firstOrCreate(
                [
                    'exam_registration_id' => $record->id,
                    'user_id'              => $userId,
                ],
                ['examiner_order' => $order]
            );
        }

        if ($record->user_id) {
            GuideExaminer::where('user_id', $record->user_id)->update([
                'guide1_id'    => $record->guide1_id,
                'guide2_id'    => $record->guide2_id,
                'examiner1_id' => $record->examiner1_id,
                'examiner2_id' => $record->examiner2_id,
                'examiner3_id' => $record->examiner3_id,
                'chief_id'     => $record->chief_id,
            ]);
        }
    }
}
