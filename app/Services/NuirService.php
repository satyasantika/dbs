<?php

namespace App\Services;

use App\Models\GuideExaminer;
use App\Models\NuirProposal;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;

class NuirService
{
    public function getActiveSetting(User $user): ?NuirSetting
    {
        $guideExaminer = GuideExaminer::where('user_id', $user->id)->first();

        if (! $guideExaminer) {
            return null;
        }

        return NuirSetting::where('year_generation', $guideExaminer->year_generation)
            ->where('active', true)
            ->first();
    }

    public function checkDeadline(NuirSetting $setting): bool
    {
        return is_null($setting->deadline) || $setting->deadline->isFuture();
    }

    public function activeSubmission(User $user): ?NuirSubmission
    {
        return NuirSubmission::where('user_id', $user->id)
            ->where('status', '!=', 'finalized')
            ->latest('id')
            ->first();
    }

    public function hasFinalizedSubmission(User $user): bool
    {
        return NuirSubmission::where('user_id', $user->id)
            ->where('status', 'finalized')
            ->exists();
    }

    public function finalizeProposal(NuirProposal $proposal): void
    {
        $proposal->update(['final' => true]);
        $proposal->submission->update(['status' => 'finalized']);

        GuideExaminer::where('user_id', $proposal->submission->user_id)
            ->update([
                'guide1_id' => $proposal->guide1_id,
                'guide2_id' => $proposal->guide2_id,
            ]);
    }

    public function hasPendingDuplicateProposal(int $submissionId, int $guide1Id, int $guide2Id): bool
    {
        return NuirProposal::where('nuir_submission_id', $submissionId)
            ->where('guide1_id', $guide1Id)
            ->where('guide2_id', $guide2Id)
            ->where('guide1_status', 'pending')
            ->where('guide2_status', 'pending')
            ->exists();
    }
}
