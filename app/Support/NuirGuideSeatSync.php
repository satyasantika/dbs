<?php

namespace App\Support;

use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\User;
use App\Services\NuirService;

class NuirGuideSeatSync
{
    public function __construct(private NuirService $nuirService)
    {
    }

    public function guideHasApprovedAllNuiFields(NuirProposal $proposal, User $guide): bool
    {
        foreach (NuirContentReview::FIELDS as $field) {
            $approved = NuirContentReview::query()
                ->where('nuir_submission_id', $proposal->nuir_submission_id)
                ->where('user_id', $guide->id)
                ->where('field', $field)
                ->where('approved', true)
                ->exists();

            if (! $approved) {
                return false;
            }
        }

        return true;
    }

    public function guideHasRevisionRequests(NuirProposal $proposal, User $guide): bool
    {
        foreach (NuirContentReview::FIELDS as $field) {
            $approved = NuirContentReview::query()
                ->where('nuir_submission_id', $proposal->nuir_submission_id)
                ->where('user_id', $guide->id)
                ->where('field', $field)
                ->value('approved');

            if ($approved === false) {
                return true;
            }
        }

        return false;
    }

    public function syncGuideSeat(NuirProposal $proposal, User $guide): NuirProposal
    {
        $proposal = $proposal->fresh(['submission']);

        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            return $proposal;
        }

        $isGuide1 = $guide->id === $proposal->guide1_id;
        $statusColumn = $isGuide1 ? 'guide1_status' : 'guide2_status';
        $respondedColumn = $isGuide1 ? 'guide1_responded_at' : 'guide2_responded_at';

        if ($proposal->{$statusColumn} === 'rejected') {
            return $proposal;
        }

        if (! $proposal->submission->isContentFinalForPembimbing()) {
            if ($proposal->{$statusColumn} === 'accepted') {
                $proposal->update([
                    $statusColumn => 'pending',
                    $respondedColumn => null,
                ]);
            }

            return $proposal->fresh();
        }

        if ($this->guideHasApprovedAllNuiFields($proposal, $guide)) {
            $proposal->update([
                $statusColumn => 'accepted',
                $respondedColumn => now(),
            ]);
        } else {
            $proposal->update([
                $statusColumn => 'pending',
                $respondedColumn => null,
            ]);
        }

        return $proposal->fresh();
    }

    public function tryFinalize(NuirProposal $proposal): NuirProposal
    {
        $proposal = $proposal->fresh();

        if ($proposal->final) {
            return $proposal;
        }

        if ($proposal->isBothAccepted()) {
            $this->nuirService->finalizeProposal($proposal);
        }

        return $proposal->fresh();
    }
}
