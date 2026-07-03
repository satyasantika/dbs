<?php

namespace App\Services;

use App\Models\GuideExaminer;
use App\Models\NuirProposal;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function hasSubmission(User $user): bool
    {
        return NuirSubmission::where('user_id', $user->id)->exists();
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
            ->where(function ($query) {
                $query->where('guide1_status', 'pending')
                    ->orWhere('guide2_status', 'pending');
            })
            ->exists();
    }

    public function deleteSubmission(NuirSubmission $submission, User $actor): void
    {
        if (! $actor->can('delete nuir submission')) {
            abort(403);
        }

        if ($submission->childSubmissions()->exists()) {
            throw ValidationException::withMessages([
                'submission' => 'Submission ini punya versi lebih baru — hapus versi terbaru terlebih dahulu.',
            ]);
        }

        if ($submission->status === 'finalized') {
            throw ValidationException::withMessages([
                'submission' => 'Submission yang sudah finalized tidak dapat dihapus.',
            ]);
        }

        DB::transaction(function () use ($submission): void {
            $quotaService = app(NuirGuideQuotaService::class);
            $seatsToRelease = [];

            foreach ($submission->proposals as $proposal) {
                foreach ([1 => $proposal->guide1_id, 2 => $proposal->guide2_id] as $guideOrder => $guideId) {
                    $status = $guideOrder === 1 ? $proposal->guide1_status : $proposal->guide2_status;

                    if ($guideId && in_array($status, ['pending', 'accepted'], true)) {
                        $seatsToRelease[$guideId.':'.$guideOrder] = ['lecturer_id' => $guideId, 'guide_order' => $guideOrder];
                    }
                }
            }

            foreach ($seatsToRelease as $seat) {
                $lecturer = User::find($seat['lecturer_id']);

                if ($lecturer) {
                    $quotaService->release($lecturer, $seat['guide_order'], $submission->year_generation);
                }
            }

            $submission->references()->delete();
            $submission->proposals()->delete();
            $submission->delete();
        });
    }
}
