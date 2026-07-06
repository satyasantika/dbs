<?php

namespace App\Support;

use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NuirValidatorReferenceStatus
{
    public static function pendingReferencesScope(Builder $query): Builder
    {
        return $query
            ->whereNull('ref_approved')
            ->whereNotExists(function ($subQuery): void {
                $subQuery->selectRaw('1')
                    ->from('nuir_revision_events')
                    ->whereColumn('nuir_revision_events.nuir_submission_id', 'nuir_references.nuir_submission_id')
                    ->whereColumn('nuir_revision_events.ref_order', 'nuir_references.ref_order')
                    ->where('nuir_revision_events.event_type', NuirRevisionEvent::TYPE_REFERENCE_REVISION);
            });
    }

    public static function awaitingRevalidationScope(Builder $query): Builder
    {
        return $query
            ->whereNull('ref_approved')
            ->whereExists(function ($subQuery): void {
                $subQuery->selectRaw('1')
                    ->from('nuir_revision_events')
                    ->whereColumn('nuir_revision_events.nuir_submission_id', 'nuir_references.nuir_submission_id')
                    ->whereColumn('nuir_revision_events.ref_order', 'nuir_references.ref_order')
                    ->where('nuir_revision_events.event_type', NuirRevisionEvent::TYPE_REFERENCE_REVISION);
            });
    }

    public static function validationCompleteSubmissionsScope(Builder $query): Builder
    {
        return $query
            ->whereHas('references')
            ->whereDoesntHave('references', fn (Builder $referenceQuery) => $referenceQuery->where(
                fn (Builder $innerQuery) => $innerQuery
                    ->whereNull('ref_approved')
                    ->orWhere('ref_approved', false),
            ));
    }

    /**
     * @return array{approved: int, needs_revision: int, pending: int, awaiting_revalidation: int}
     */
    public static function referenceCounts(NuirSubmission $submission): array
    {
        $references = $submission->relationLoaded('references')
            ? $submission->references
            : $submission->references()->get();

        $revisionRefOrders = self::referenceRevisionOrders($submission);

        $counts = [
            'approved' => 0,
            'needs_revision' => 0,
            'pending' => 0,
            'awaiting_revalidation' => 0,
        ];

        foreach ($references as $reference) {
            if ($reference->ref_approved === true) {
                $counts['approved']++;

                continue;
            }

            if ($reference->ref_approved === false) {
                $counts['needs_revision']++;

                continue;
            }

            if ($revisionRefOrders->contains($reference->ref_order)) {
                $counts['awaiting_revalidation']++;

                continue;
            }

            $counts['pending']++;
        }

        return $counts;
    }

    public static function referenceBreakdownSummary(NuirSubmission $submission): string
    {
        $counts = self::referenceCounts($submission);
        $parts = [];

        if ($counts['approved'] > 0) {
            $parts[] = $counts['approved'].' disetujui';
        }

        $pendingTotal = $counts['pending'] + $counts['awaiting_revalidation'];
        if ($pendingTotal > 0) {
            $parts[] = $pendingTotal.' pending';
        }

        if ($counts['needs_revision'] > 0) {
            $parts[] = $counts['needs_revision'].' revisi';
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Belum ada referensi';
    }

    /**
     * @return list<array{label: string, color: string}>
     */
    public static function referenceBreakdownBadges(NuirSubmission $submission): array
    {
        $counts = self::referenceCounts($submission);
        $badges = [];

        if ($counts['approved'] > 0) {
            $badges[] = [
                'label' => $counts['approved'].' disetujui',
                'color' => 'success',
            ];
        }

        $pendingTotal = $counts['pending'] + $counts['awaiting_revalidation'];
        if ($pendingTotal > 0) {
            $badges[] = [
                'label' => $pendingTotal.' pending',
                'color' => 'warning',
            ];
        }

        if ($counts['needs_revision'] > 0) {
            $badges[] = [
                'label' => $counts['needs_revision'].' revisi',
                'color' => 'danger',
            ];
        }

        return $badges;
    }

    /**
     * Status progres validasi referensi untuk badge Status di panel validator.
     * "Divalidasi" berarti disetujui validator, selaras dengan
     * validationCompleteSubmissionsScope() (view "Validasi Selesai").
     *
     * @return array{label: string, color: string}
     */
    public static function validationProgressBadge(NuirSubmission $submission): array
    {
        $counts = self::referenceCounts($submission);
        $total = array_sum($counts);
        $approved = $counts['approved'];

        if ($total === 0 || $approved === 0) {
            return ['label' => 'Referensi Belum Divalidasi', 'color' => 'gray'];
        }

        if ($approved < $total) {
            return ['label' => 'Referensi Sebagian Divalidasi', 'color' => 'warning'];
        }

        return ['label' => 'Referensi Tervalidasi', 'color' => 'success'];
    }

    public static function referenceHasValidatorResponse(NuirReference $reference): bool
    {
        if ($reference->ref_approved !== null) {
            return true;
        }

        $submission = $reference->relationLoaded('submission')
            ? $reference->submission
            : $reference->submission()->first();

        if (! $submission) {
            return false;
        }

        return self::referenceRevisionOrders($submission)->contains($reference->ref_order);
    }

    public static function referenceActivitySummary(NuirReference $reference): string
    {
        if (! self::referenceHasValidatorResponse($reference)) {
            $assignedAt = $reference->submission?->assignment?->assigned_at;

            return $assignedAt instanceof Carbon
                ? 'Ditugaskan '.$assignedAt->locale('id')->diffForHumans()
                : 'Belum ditugaskan';
        }

        $updatedAt = $reference->updated_at;

        return $updatedAt instanceof Carbon
            ? 'Diperbarui '.$updatedAt->locale('id')->diffForHumans()
            : 'Belum diperbarui';
    }

    public static function validatorHasRespondedToAnyReference(NuirSubmission $submission): bool
    {
        $counts = self::referenceCounts($submission);

        return ($counts['approved'] + $counts['needs_revision'] + $counts['awaiting_revalidation']) > 0;
    }

    public static function submissionActivitySummary(NuirSubmission $submission): string
    {
        if (! self::validatorHasRespondedToAnyReference($submission)) {
            $assignedAt = $submission->assignment?->assigned_at;

            return $assignedAt instanceof Carbon
                ? 'Ditugaskan '.$assignedAt->locale('id')->diffForHumans()
                : 'Belum ditugaskan';
        }

        $updatedAt = $submission->updated_at;

        return $updatedAt instanceof Carbon
            ? 'Diperbarui '.$updatedAt->locale('id')->diffForHumans()
            : 'Belum diperbarui';
    }

    /**
     * @return Collection<int, int>
     */
    protected static function referenceRevisionOrders(NuirSubmission $submission): Collection
    {
        return NuirRevisionEvent::query()
            ->whereIn('nuir_submission_id', self::submissionLineageIds($submission))
            ->where('event_type', NuirRevisionEvent::TYPE_REFERENCE_REVISION)
            ->whereNotNull('ref_order')
            ->pluck('ref_order')
            ->unique()
            ->values();
    }

    /**
     * @return array<int, int>
     */
    protected static function submissionLineageIds(NuirSubmission $submission): array
    {
        $ids = [$submission->id];
        $current = $submission;

        while ($current->parent_submission_id) {
            $current = $current->parentSubmission ?? NuirSubmission::query()->find($current->parent_submission_id);

            if (! $current) {
                break;
            }

            $ids[] = $current->id;
        }

        return $ids;
    }
}
