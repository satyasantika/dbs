<?php

namespace App\Services;

use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Support\NuirMahasiswaFieldStatus;
use App\Support\NuirRevisionGate;
use App\Support\NuirTextLimits;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NuirMahasiswaWorkspaceService
{
    public function __construct(
        private NuirService $nuirService,
        private NuirRevisionHistoryService $revisionHistory,
        private NuirProposalService $proposalService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function workspaceData(User $user): array
    {
        $setting = $this->nuirService->getActiveSetting($user);

        if (! $setting || ! $setting->active) {
            return [
                'setting' => $setting,
                'submission' => null,
                'closed' => true,
                'stage3' => false,
            ];
        }

        if ($setting->stage === 3) {
            return [
                'setting' => $setting,
                'submission' => null,
                'closed' => false,
                'stage3' => true,
            ];
        }

        $submission = $this->nuirService->activeSubmission($user);

        if (! $submission) {
            return [
                'setting' => $setting,
                'submission' => null,
                'closed' => false,
                'stage3' => false,
            ];
        }

        $submission->load(['references', 'contentReviews', 'proposals.guide1', 'proposals.guide2', 'assignment']);

        return [
            'setting' => $setting,
            'submission' => $submission,
            'closed' => false,
            'stage3' => false,
            'activeProposal' => $this->activeProposal($submission),
            'nuiComplete' => $this->isNuiComplete($submission, $setting),
            'nuiFieldsFilled' => $this->hasAllNuiFieldsFilled($submission),
            'rejectionHistory' => $this->revisionHistory->rejectionHistoryForSubmission($submission)->all(),
            'referenceSlots' => range(1, (int) ($setting->max_references ?? 10)),
            'lecturersP1' => $this->proposalService->lecturersForSeat($user, $submission->year_generation, 1, $submission->lockedSeats()),
            'lecturersP2' => $this->proposalService->lecturersForSeat($user, $submission->year_generation, 2, $submission->lockedSeats()),
        ];
    }

    public function createSubmission(User $user): NuirSubmission
    {
        $setting = $this->requireWritableSetting($user);

        if ($this->nuirService->hasFinalizedSubmission($user)) {
            throw ValidationException::withMessages([
                'submission' => 'Pembimbing Anda sudah ditetapkan.',
            ]);
        }

        if ($this->nuirService->activeSubmission($user)) {
            throw ValidationException::withMessages([
                'submission' => 'Pengajuan NUIR sudah ada.',
            ]);
        }

        if (! $this->nuirService->checkDeadline($setting)) {
            throw ValidationException::withMessages([
                'submission' => 'Batas pengajuan NUIR telah berakhir.',
            ]);
        }

        return NuirSubmission::create([
            'user_id' => $user->id,
            'year_generation' => $setting->year_generation,
            'title' => '',
            'status' => 'title_slot',
        ]);
    }

    public function saveNuiField(NuirSubmission $submission, User $user, string $field, string $value): void
    {
        $this->authorizeOwner($submission, $user);
        $setting = $this->requireWritableSetting($user);

        if ($submission->hasActiveFinalProposal()) {
            throw ValidationException::withMessages([
                $field => 'Pengajuan sudah final dan tidak dapat diubah.',
            ]);
        }

        $value = trim($value);

        if ($field === 'title') {
            NuirTextLimits::assertTitleField($value, $setting);

            if (! $submission->isNuiFieldEditable('title') && ! $submission->isTitleSlot()) {
                throw ValidationException::withMessages([
                    'title' => 'Judul tidak dapat diubah saat ini.',
                ]);
            }

            $submission->update([
                'title' => $value,
                'title_saved_at' => now(),
            ]);
            $this->maybePromoteToSubmitted($submission->fresh(), $setting);

            return;
        }

        if (! in_array($field, ['novelty', 'urgency', 'impact'], true)) {
            abort(422);
        }

        if (! $submission->isNuiFieldEditable($field)) {
            throw ValidationException::withMessages([
                $field => ucfirst($field).' tidak dapat diubah saat ini.',
            ]);
        }

        $message = NuirTextLimits::validateNuiField($value, $setting, $field);

        if ($message !== null) {
            throw ValidationException::withMessages([$field => $message]);
        }

        $submission->update([
            $field => $value,
            "{$field}_saved_at" => now(),
        ]);

        if ($submission->isPartialNuiEditable()) {
            NuirRevisionGate::clearRejectedContentReviews($submission, $field);
        }

        $this->maybePromoteToSubmitted($submission->fresh(), $setting);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveReference(NuirSubmission $submission, User $user, int $order, array $data): void
    {
        $this->authorizeOwner($submission, $user);
        $setting = $this->requireWritableSetting($user);

        if (! $this->isNuiComplete($submission, $setting)) {
            throw ValidationException::withMessages([
                'reference' => 'Lengkapi Judul, Novelty, Urgency, dan Impact terlebih dahulu.',
            ]);
        }

        $maxReferences = (int) ($setting->max_references ?? 10);

        if ($order < 1 || $order > $maxReferences) {
            abort(422);
        }

        $existing = $submission->references()->where('ref_order', $order)->first();

        if ($existing?->ref_approved === true) {
            throw ValidationException::withMessages([
                'reference' => 'Referensi #'.$order.' sudah disetujui dan tidak dapat diubah.',
            ]);
        }

        $attributes = [
            'link_ojs' => $data['link_ojs'] ?? null,
            'indexer_name' => $data['indexer_name'] ?? null,
            'link_index' => $data['link_index'] ?? null,
            'link_drive' => $data['link_drive'] ?? null,
            'quote' => $data['quote'] ?? null,
            'relevance' => $data['relevance'] ?? null,
        ];

        if (! collect($attributes)->contains(fn ($value) => filled($value))) {
            throw ValidationException::withMessages([
                'reference' => 'Isi minimal satu bagian referensi sebelum menyimpan.',
            ]);
        }

        if ($existing !== null) {
            $attributes['ref_approved'] = null;
            $attributes['ref_note'] = null;
            $attributes['ref_revision_fields'] = null;
        }

        NuirReference::updateOrCreate(
            [
                'nuir_submission_id' => $submission->id,
                'ref_order' => $order,
            ],
            $attributes,
        );

        $this->maybePromoteToSubmitted($submission->fresh(), $setting);
    }

    public function proposeGuideSeat(NuirSubmission $submission, User $user, int $seat, int $guideId): NuirProposal
    {
        $this->authorizeOwner($submission, $user);
        $setting = $this->requireWritableSetting($user);

        if ($submission->hasActiveFinalProposal()) {
            throw ValidationException::withMessages([
                'guide' => 'Pembimbing sudah ditetapkan.',
            ]);
        }

        return $this->proposalService->proposeSeat($submission, $user, $seat, $guideId);
    }

    public function hasAllNuiFieldsFilled(NuirSubmission $submission): bool
    {
        return $this->missingNuiFieldLabels($submission) === [];
    }

    /**
     * @return list<string>
     */
    public function missingNuiFieldLabels(NuirSubmission $submission): array
    {
        $labels = [
            'title' => 'Judul',
            'novelty' => 'Novelty',
            'urgency' => 'Urgency',
            'impact' => 'Impact',
        ];

        $missing = [];

        foreach ($labels as $field => $label) {
            if (blank($submission->{$field})) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function hasTitleBeenSaved(?NuirSubmission $submission): bool
    {
        return $submission?->title_saved_at !== null;
    }

    public function isNuiFieldFilled(?NuirSubmission $submission, string $field): bool
    {
        return $submission !== null && filled($submission->{$field});
    }

    public function isReferenceSlotFilled(?NuirReference $reference): bool
    {
        return $reference !== null && NuirMahasiswaFieldStatus::referenceHasContent($reference);
    }

    public function isNuiComplete(NuirSubmission $submission, NuirSetting $setting): bool
    {
        if (! $this->hasAllNuiFieldsFilled($submission)) {
            return false;
        }

        foreach (['title', 'novelty', 'urgency', 'impact'] as $field) {
            if ($field === 'title') {
                if (NuirTextLimits::validateTitleField($submission->title, $setting) !== null) {
                    return false;
                }

                continue;
            }

            if (NuirTextLimits::validateNuiField($submission->{$field}, $setting, $field) !== null) {
                return false;
            }
        }

        return true;
    }

    public function fieldHistory(NuirSubmission $submission, string $field): Collection
    {
        return $this->revisionHistory->contentFieldHistory($submission, $field);
    }

    public function referenceHistory(NuirSubmission $submission, int $refOrder): Collection
    {
        return $this->revisionHistory->referenceRevisionHistory($submission, $refOrder);
    }

    public function wordLimitHint(NuirSetting $setting, string $field): string
    {
        $elementLabel = match ($field) {
            'title' => 'Judul',
            'novelty' => 'Novelty',
            'urgency' => 'Urgency',
            'impact' => 'Impact',
            default => ucfirst($field),
        };

        if ($field === 'title') {
            $max = $setting->max_words_title;

            if ($max) {
                return "isi {$elementLabel} maks. {$max} kata";
            }

            return "isi {$elementLabel} wajib diisi";
        }

        $max = $setting->{"max_words_{$field}"};

        if ($max) {
            return "isi {$elementLabel} maks. {$max} kata";
        }

        return "isi {$elementLabel} wajib diisi";
    }

    public function activeProposal(NuirSubmission $submission): ?NuirProposal
    {
        return $submission->proposals()
            ->where('final', false)
            ->latest('id')
            ->first();
    }

    public function guideSeatState(NuirSubmission $submission, NuirProposal $proposal, int $seat): array
    {
        $guideId = $seat === 1 ? $proposal->guide1_id : $proposal->guide2_id;
        $status = $seat === 1 ? $proposal->guide1_status : $proposal->guide2_status;
        $note = $seat === 1 ? $proposal->guide1_note : $proposal->guide2_note;
        $locked = $submission->lockedSeats()['guide'.$seat] ?? null;

        $canChange = $status === 'rejected' || ($guideId === null && $status === 'pending');

        return [
            'guide_id' => $guideId,
            'status' => $status,
            'note' => $note,
            'locked' => $locked,
            'can_change' => $canChange && ! $submission->hasActiveFinalProposal(),
            'is_readonly' => filled($guideId) && $status !== 'rejected',
        ];
    }

    protected function maybePromoteToSubmitted(NuirSubmission $submission, NuirSetting $setting): void
    {
        if (! $this->isNuiComplete($submission, $setting)) {
            return;
        }

        if (in_array($submission->status, ['title_slot', 'draft'], true)) {
            $submission->update(['status' => 'submitted']);
        }
    }

    protected function authorizeOwner(NuirSubmission $submission, User $user): void
    {
        if ($submission->user_id !== $user->id) {
            abort(403);
        }
    }

    protected function requireWritableSetting(User $user): NuirSetting
    {
        $setting = $this->nuirService->getActiveSetting($user);

        if (! $setting || ! $setting->active || $setting->stage === 3) {
            abort(403);
        }

        if (! $this->nuirService->checkDeadline($setting)) {
            throw ValidationException::withMessages([
                'submission' => 'Batas pengajuan NUIR telah berakhir.',
            ]);
        }

        return $setting;
    }
}
