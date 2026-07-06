<?php

namespace App\Services;

use App\Models\NuirAssignment;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirReferenceReview;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Support\NuirGuideSeatSync;
use App\Support\NuirReferenceRevisionFields;
use Illuminate\Validation\ValidationException;

class NuirAssignmentService
{
    public function __construct(
        private NuirGuideSeatSync $guideSeatSync,
        private NuirRevisionHistoryService $revisionHistory,
    ) {
    }

    public function assignValidator(NuirSubmission $submission, User $validator, User $manajer): NuirAssignment
    {
        if (! $manajer->can('delegate nuir validator')) {
            abort(403);
        }

        if (! $validator->hasRole('validator nuir')) {
            throw ValidationException::withMessages([
                'validator_id' => 'Validator harus memiliki role validator NUIR.',
            ]);
        }

        if (! $submission->isValidatorReviewable()) {
            throw ValidationException::withMessages([
                'nuir_submission_id' => 'Submission masih draft dan belum dapat didelegasikan.',
            ]);
        }

        return NuirAssignment::updateOrCreate(
            ['nuir_submission_id' => $submission->id],
            [
                'validator_id' => $validator->id,
                'assigned_by' => $manajer->id,
                'assigned_at' => now(),
            ],
        );
    }

    public function validatorCanReview(NuirSubmission $submission, User $validator): bool
    {
        if (! $validator->can('validate nuir references')) {
            return false;
        }

        return NuirAssignment::where('nuir_submission_id', $submission->id)
            ->where('validator_id', $validator->id)
            ->exists();
    }

    public function reviewReferenceAsValidator(
        NuirReference $reference,
        User $validator,
        bool $approved,
        ?string $note = null,
        ?array $revisionFields = null,
    ): void {
        $submission = $reference->submission;

        if (! $this->validatorCanReview($submission, $validator)) {
            abort(403);
        }

        if (! $submission->isValidatorReviewable()) {
            abort(403, 'Submission masih draft.');
        }

        if ($approved) {
            \App\Support\NuirReferenceExistence::assertVerifiable($reference);
        } else {
            if (blank($note)) {
                throw ValidationException::withMessages([
                    'ref_note' => 'Catatan wajib diisi saat meminta revisi referensi.',
                ]);
            }

            NuirReferenceRevisionFields::assertSelectedForRevision($revisionFields);

            $this->revisionHistory->logReferenceRevision(
                $reference,
                $validator,
                NuirRevisionEvent::ROLE_VALIDATOR,
                $note,
                NuirReferenceRevisionFields::normalize($revisionFields),
            );
        }

        app(NuirReviewService::class)->reviewReference(
            $reference,
            $approved,
            $note,
            recordHistory: false,
            revisionFields: $revisionFields,
        );
    }

    public function cancelReferenceApprovalAsValidator(NuirReference $reference, User $validator): void
    {
        $submission = $reference->submission;

        if (! $this->validatorCanReview($submission, $validator)) {
            abort(403);
        }

        if ($submission->isFinalized()) {
            throw ValidationException::withMessages([
                'reference' => 'Submission sudah disahkan manajer; persetujuan referensi tidak dapat dibatalkan.',
            ]);
        }

        if ($reference->ref_approved !== true) {
            return;
        }

        app(NuirReviewService::class)->cancelReferenceApproval($reference);
    }

    /**
     * Guide requests revision on a reference. Unlike reviewReferenceAsGuide()
     * (a private per-guide opinion), this writes to the reference's real
     * ref_approved/ref_note fields and logs a shared NuirRevisionEvent —
     * the same fields/history validators use — so it shows up correctly
     * attributed ("Pembimbing 1/2") in the shared revision timeline.
     *
     * @param  list<string>  $revisionFields
     */
    public function requestReferenceRevisionAsGuide(
        NuirReference $reference,
        NuirProposal $proposal,
        User $guide,
        string $note,
        array $revisionFields,
    ): void {
        if (! $guide->can('respond nuir proposal')) {
            abort(403);
        }

        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            abort(403);
        }

        if ($reference->nuir_submission_id !== $proposal->nuir_submission_id) {
            abort(403);
        }

        if (blank($note)) {
            throw ValidationException::withMessages([
                'ref_note' => 'Catatan wajib diisi saat meminta revisi referensi.',
            ]);
        }

        NuirReferenceRevisionFields::assertSelectedForRevision($revisionFields);

        $role = $guide->id === $proposal->guide1_id
            ? NuirRevisionEvent::ROLE_GUIDE1
            : NuirRevisionEvent::ROLE_GUIDE2;

        $this->revisionHistory->logReferenceRevision(
            $reference,
            $guide,
            $role,
            $note,
            NuirReferenceRevisionFields::normalize($revisionFields),
        );

        app(NuirReviewService::class)->reviewReference(
            $reference,
            false,
            $note,
            recordHistory: false,
            revisionFields: $revisionFields,
        );
    }

    public function reviewReferenceAsGuide(
        NuirReference $reference,
        NuirProposal $proposal,
        User $guide,
        bool $approved,
        ?string $note = null,
    ): void {
        if (! $guide->can('respond nuir proposal')) {
            abort(403);
        }

        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            abort(403);
        }

        if ($reference->nuir_submission_id !== $proposal->nuir_submission_id) {
            abort(403);
        }

        if (! $approved && blank($note)) {
            throw ValidationException::withMessages([
                'note' => 'Catatan wajib diisi saat referensi ditolak.',
            ]);
        }

        $role = $guide->id === $proposal->guide1_id
            ? NuirReferenceReview::ROLE_GUIDE1
            : NuirReferenceReview::ROLE_GUIDE2;

        NuirReferenceReview::updateOrCreate(
            [
                'nuir_reference_id' => $reference->id,
                'user_id' => $guide->id,
                'role' => $role,
            ],
            [
                'approved' => $approved,
                'note' => $approved ? null : $note,
                'reviewed_at' => now(),
            ],
        );
    }

    public function cancelReferenceReviewAsGuide(
        NuirReference $reference,
        NuirProposal $proposal,
        User $guide,
    ): void {
        if (! $guide->can('respond nuir proposal')) {
            abort(403);
        }

        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            abort(403);
        }

        if ($reference->nuir_submission_id !== $proposal->nuir_submission_id) {
            abort(403);
        }

        $role = $guide->id === $proposal->guide1_id
            ? NuirReferenceReview::ROLE_GUIDE1
            : NuirReferenceReview::ROLE_GUIDE2;

        NuirReferenceReview::query()
            ->where('nuir_reference_id', $reference->id)
            ->where('user_id', $guide->id)
            ->where('role', $role)
            ->where('approved', true)
            ->delete();
    }

    public function cancelContentReviewAsGuide(
        NuirSubmission $submission,
        NuirProposal $proposal,
        User $guide,
        string $field,
    ): void {
        if (! $guide->can('respond nuir proposal')) {
            abort(403);
        }

        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            abort(403);
        }

        if ($proposal->nuir_submission_id !== $submission->id) {
            abort(403);
        }

        if (! in_array($field, NuirContentReview::FIELDS, true)) {
            abort(422);
        }

        NuirContentReview::query()
            ->where('nuir_submission_id', $submission->id)
            ->where('user_id', $guide->id)
            ->where('field', $field)
            ->where('approved', true)
            ->delete();

        $this->guideSeatSync->syncGuideSeat($proposal, $guide);
    }

    public function reviewContentAsGuide(
        NuirSubmission $submission,
        NuirProposal $proposal,
        User $guide,
        string $field,
        bool $approved,
        ?string $note = null,
    ): void {
        if (! $guide->can('respond nuir proposal')) {
            abort(403);
        }

        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            abort(403);
        }

        if ($proposal->nuir_submission_id !== $submission->id) {
            abort(403);
        }

        if (! in_array($field, NuirContentReview::FIELDS, true)) {
            abort(422);
        }

        if (! $approved && blank($note)) {
            throw ValidationException::withMessages([
                'note' => 'Catatan wajib diisi saat meminta revisi.',
            ]);
        }

        $role = $guide->id === $proposal->guide1_id
            ? NuirContentReview::ROLE_GUIDE1
            : NuirContentReview::ROLE_GUIDE2;

        NuirContentReview::updateOrCreate(
            [
                'nuir_submission_id' => $submission->id,
                'user_id' => $guide->id,
                'field' => $field,
            ],
            [
                'role' => $role,
                'approved' => $approved,
                'note' => $approved ? null : $note,
                'reviewed_at' => now(),
            ],
        );

        if (! $approved) {
            $this->revisionHistory->logNuiRevision($submission, $guide, $role, $field, $note);
        }

        $this->guideSeatSync->syncGuideSeat($proposal, $guide);
    }

    public function rejectProposalAsGuide(NuirProposal $proposal, User $guide, string $note): void
    {
        if (! $guide->can('respond nuir proposal')) {
            abort(403);
        }

        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            abort(403);
        }

        if ($proposal->final) {
            abort(403);
        }

        $currentStatus = $guide->id === $proposal->guide1_id ? $proposal->guide1_status : $proposal->guide2_status;

        if (! in_array($currentStatus, ['pending', 'accepted'], true)) {
            abort(403);
        }

        $guideOrder = $guide->id === $proposal->guide1_id ? 1 : 2;
        $statusColumn = $guideOrder === 1 ? 'guide1_status' : 'guide2_status';
        $noteColumn = $guideOrder === 1 ? 'guide1_note' : 'guide2_note';
        $respondedColumn = $guideOrder === 1 ? 'guide1_responded_at' : 'guide2_responded_at';

        $proposal->update([
            $statusColumn => 'rejected',
            $noteColumn => $note,
            $respondedColumn => now(),
        ]);

        $this->revisionHistory->logProposalRejection($proposal->fresh(), $guide, $guideOrder, $note);
        app(NuirProposalService::class)->releaseSeatQuota($proposal->fresh(), $guideOrder);
    }

    public function guideHasApprovedAllNuiFields(NuirProposal $proposal, User $guide): bool
    {
        return $this->guideSeatSync->guideHasApprovedAllNuiFields($proposal, $guide);
    }

    public function guideCanAcceptProposal(NuirProposal $proposal, User $guide): bool
    {
        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            return false;
        }

        if (! $this->guideHasApprovedAllNuiFields($proposal, $guide)) {
            return false;
        }

        if ($guide->id === $proposal->guide1_id) {
            return $proposal->guide1_status === 'pending';
        }

        return $proposal->guide2_status === 'pending';
    }

    public function validators(): \Illuminate\Support\Collection
    {
        return User::role('validator nuir')->orderBy('name')->get();
    }
}
