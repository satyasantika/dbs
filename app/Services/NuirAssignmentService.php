<?php

namespace App\Services;

use App\Models\NuirAssignment;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirReferenceReview;
use App\Models\NuirSubmission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class NuirAssignmentService
{
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

    public function reviewReferenceAsValidator(NuirReference $reference, User $validator, bool $approved, ?string $note = null): void
    {
        $submission = $reference->submission;

        if (! $this->validatorCanReview($submission, $validator)) {
            abort(403);
        }

        if (! $submission->isValidatorReviewable()) {
            abort(403, 'Submission masih draft.');
        }

        app(NuirReviewService::class)->reviewReference($reference, $approved, $note);
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
                'note' => 'Catatan wajib diisi saat konten NUIR ditolak.',
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
    }

    public function guideCanAcceptProposal(NuirProposal $proposal, User $guide): bool
    {
        if ($proposal->guide1_id !== $guide->id && $proposal->guide2_id !== $guide->id) {
            return false;
        }

        if (! $proposal->submission->isContentFinalForPembimbing()) {
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
