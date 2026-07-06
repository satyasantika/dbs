<?php

namespace App\Filament\NuirValidator\Concerns;

use App\Models\NuirSubmission;
use App\Services\NuirRevisionHistoryService;
use App\Support\NuirReferenceRevisionFields;

trait BuildsValidatorNuirSubmissionInfolist
{
    /**
     * @return array<string, mixed>
     */
    public static function referencesPanelViewData(NuirSubmission $record): array
    {
        $historyService = app(NuirRevisionHistoryService::class);
        $references = $record->references()->orderBy('ref_order')->get();

        return [
            'references' => $references,
            'revisionFieldOptions' => NuirReferenceRevisionFields::options(),
            'submissionFinalized' => $record->isFinalized(),
            'histories' => $references
                ->pluck('ref_order')
                ->mapWithKeys(fn (int $refOrder) => [
                    $refOrder => $historyService->referenceRevisionHistory($record, $refOrder)->all(),
                ])
                ->all(),
            'revisionRounds' => $references
                ->pluck('ref_order')
                ->mapWithKeys(fn (int $refOrder) => [
                    $refOrder => $historyService->referenceRevisionRound($record, $refOrder),
                ])
                ->all(),
            'showRevisionBadges' => $references
                ->pluck('ref_order')
                ->mapWithKeys(fn (int $refOrder) => [
                    $refOrder => $historyService->referenceHasRevisionHistory($record, $refOrder),
                ])
                ->all(),
        ];
    }
}
