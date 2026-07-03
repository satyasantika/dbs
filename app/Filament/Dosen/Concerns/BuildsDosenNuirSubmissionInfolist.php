<?php

namespace App\Filament\Dosen\Concerns;

use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirRevisionHistoryService;
use App\Support\NuirContentFieldPresenter;
use App\Support\NuirReferenceRevisionFields;

trait BuildsDosenNuirSubmissionInfolist
{
    /**
     * @return array<string, mixed>
     */
    protected function dosenContentFieldViewData(NuirSubmission $record, NuirProposal $proposal, User $guide, string $field): array
    {
        $config = NuirContentFieldPresenter::config($field);
        $content = $record->{$field} ?? '';
        $historyService = app(NuirRevisionHistoryService::class);

        $myReview = NuirContentReview::query()
            ->where('nuir_submission_id', $record->id)
            ->where('user_id', $guide->id)
            ->where('field', $field)
            ->first();

        return [
            ...$config,
            'content' => filled($content) ? $content : '—',
            'wordMeta' => NuirContentFieldPresenter::wordCountDescription($record, $field),
            'isEmpty' => blank($content),
            'revisionRound' => $historyService->contentFieldRevisionNumber($record, $field),
            'versionLabel' => $historyService->contentFieldVersionLabel($record, $field),
            'showRevisionBadge' => $historyService->contentFieldHasRevisionHistory($record, $field),
            'revisionHistory' => $historyService->contentFieldHistory($record, $field)->all(),
            'myApproved' => $myReview?->approved,
            'myNote' => $myReview?->note,
            'canReview' => ! $proposal->final && $this->dosenSeatStatus($proposal, $guide) !== 'rejected',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dosenReferencesPanelViewData(NuirSubmission $record, NuirProposal $proposal, User $guide): array
    {
        $historyService = app(NuirRevisionHistoryService::class);
        $references = $record->references()->orderBy('ref_order')->get();

        return [
            'references' => $references,
            'revisionFieldOptions' => NuirReferenceRevisionFields::options(),
            'canReview' => ! $proposal->final && $this->dosenSeatStatus($proposal, $guide) !== 'rejected',
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

    protected function dosenSeatStatus(NuirProposal $proposal, User $guide): ?string
    {
        if ($proposal->guide1_id === $guide->id) {
            return $proposal->guide1_status;
        }

        if ($proposal->guide2_id === $guide->id) {
            return $proposal->guide2_status;
        }

        return null;
    }

    protected function dosenSeatLabel(NuirProposal $proposal, User $guide): ?string
    {
        return match ($guide->id) {
            $proposal->guide1_id => 'P1',
            $proposal->guide2_id => 'P2',
            default => null,
        };
    }
}
