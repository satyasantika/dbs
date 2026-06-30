<?php

namespace App\Services;

use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

class NuirRevisionHistoryService
{
    public function logNuiRevision(
        NuirSubmission $submission,
        User $actor,
        string $role,
        string $field,
        string $note,
    ): NuirRevisionEvent {
        return $this->record($submission, $actor, $role, NuirRevisionEvent::TYPE_NUI_REVISION, $field, $note);
    }

    public function logReferenceRevision(
        NuirReference $reference,
        User $actor,
        string $role,
        string $note,
    ): NuirRevisionEvent {
        return $this->record(
            $reference->submission,
            $actor,
            $role,
            NuirRevisionEvent::TYPE_REFERENCE_REVISION,
            (string) $reference->ref_order,
            $note,
            refOrder: $reference->ref_order,
        );
    }

    public function logProposalRejection(
        NuirProposal $proposal,
        User $actor,
        int $guideOrder,
        string $note,
    ): NuirRevisionEvent {
        $role = $guideOrder === 1 ? NuirRevisionEvent::ROLE_GUIDE1 : NuirRevisionEvent::ROLE_GUIDE2;

        return $this->record(
            $proposal->submission,
            $actor,
            $role,
            NuirRevisionEvent::TYPE_PROPOSAL_REJECTION,
            'guide'.$guideOrder,
            $note,
            proposalId: $proposal->id,
        );
    }

    public function logDbsRevision(NuirSubmission $submission, User $actor, string $note): NuirRevisionEvent
    {
        return $this->record(
            $submission,
            $actor,
            NuirRevisionEvent::ROLE_DBS,
            NuirRevisionEvent::TYPE_DBS_REVISION,
            'submission',
            $note,
        );
    }

    /**
     * @return list<int>
     */
    public function versionLineageIds(NuirSubmission $submission): array
    {
        $ids = [];
        $current = $submission->loadMissing('parentSubmission');

        while ($current !== null) {
            $ids[] = $current->id;
            $current = $current->parentSubmission;
        }

        return $ids;
    }

    public function historyForLineage(NuirSubmission $submission): Collection
    {
        $ids = $this->versionLineageIds($submission);

        return NuirRevisionEvent::query()
            ->with(['actor', 'submission'])
            ->whereIn('nuir_submission_id', $ids)
            ->orderByDesc('recorded_at')
            ->get();
    }

    public function rejectionHistoryForSubmission(NuirSubmission $submission): Collection
    {
        $ids = $this->versionLineageIds($submission);

        return NuirRevisionEvent::query()
            ->with(['actor', 'proposal.guide1', 'proposal.guide2'])
            ->whereIn('nuir_submission_id', $ids)
            ->where('event_type', NuirRevisionEvent::TYPE_PROPOSAL_REJECTION)
            ->orderByDesc('recorded_at')
            ->get();
    }

    private function record(
        NuirSubmission $submission,
        User $actor,
        string $role,
        string $eventType,
        string $subject,
        string $note,
        ?int $refOrder = null,
        ?int $proposalId = null,
    ): NuirRevisionEvent {
        return NuirRevisionEvent::create([
            'nuir_submission_id' => $submission->id,
            'submission_version' => $submission->version,
            'actor_id' => $actor->id,
            'actor_role' => $role,
            'event_type' => $eventType,
            'subject' => $subject,
            'ref_order' => $refOrder,
            'nuir_proposal_id' => $proposalId,
            'note' => $note,
            'recorded_at' => now(),
        ]);
    }
}
