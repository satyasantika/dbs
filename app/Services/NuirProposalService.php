<?php

namespace App\Services;

use App\Models\GuideAllocation;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NuirProposalService
{
    private const PROPOSABLE_STATUSES = ['title_slot', 'draft', 'submitted', 'revision', 'content_ok'];

    public function __construct(
        private NuirService $nuirService,
        private NuirGuideQuotaService $quotaService,
        private NuirRevisionHistoryService $revisionHistory,
    ) {
    }

    public function getIndexData(User $user): array
    {
        $proposals = NuirProposal::with(['submission', 'guide1', 'guide2'])
            ->whereHas('submission', fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get();

        return [
            'proposals' => $proposals,
            'finalProposal' => $proposals->firstWhere('final', true),
            'proposableSubmission' => NuirSubmission::where('user_id', $user->id)
                ->whereIn('status', self::PROPOSABLE_STATUSES)
                ->latest('id')
                ->first(),
        ];
    }

    public function createFormData(User $user): array|RedirectResponse
    {
        $setting = $this->nuirService->getActiveSetting($user);

        if (! $setting || $setting->stage === 3) {
            abort(403);
        }

        if ($this->hasFinalProposal($user)) {
            return to_route('nuir.proposal.index')->with('warning', 'Pembimbing sudah ditetapkan.');
        }

        $submission = NuirSubmission::where('user_id', $user->id)
            ->whereIn('status', self::PROPOSABLE_STATUSES)
            ->latest('id')
            ->first();

        if (! $submission) {
            abort(403);
        }

        $previousRejected = NuirProposal::where('nuir_submission_id', $submission->id)
            ->where(function ($query) {
                $query->where('guide1_status', 'rejected')
                    ->orWhere('guide2_status', 'rejected');
            })
            ->exists();

        $lockedSeats = $submission->lockedSeats();

        return [
            'submission' => $submission,
            'previousRejected' => $previousRejected,
            'lockedSeats' => $lockedSeats,
            'lecturersP1' => $this->lecturersForSeat($user, $submission->year_generation, 1, $lockedSeats),
            'lecturersP2' => $this->lecturersForSeat($user, $submission->year_generation, 2, $lockedSeats),
        ];
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($this->hasFinalProposal($user)) {
            return to_route('nuir.proposal.index')->with('warning', 'Pembimbing sudah ditetapkan.');
        }

        $setting = $this->nuirService->getActiveSetting($user);
        if ($setting && ! $this->nuirService->checkDeadline($setting)) {
            return back()->with('warning', 'Batas pengajuan NUIR telah berakhir.')->withInput();
        }

        $data = $request->validate([
            'nuir_submission_id' => [
                'required',
                Rule::exists('nuir_submissions', 'id')->where(
                    fn ($q) => $q->where('user_id', $user->id)->whereIn('status', self::PROPOSABLE_STATUSES)
                ),
            ],
            'guide1_id' => ['required', Rule::exists('users', 'id')],
            'guide2_id' => ['required', 'different:guide1_id', Rule::exists('users', 'id')],
        ]);

        $submission = NuirSubmission::findOrFail($data['nuir_submission_id']);
        \App\Support\NuirRevisionGate::assertRevisionComplete($submission);
        $lockedSeats = $submission->lockedSeats();

        if ($lockedSeats['guide1'] && (int) $data['guide1_id'] !== $lockedSeats['guide1']['id']) {
            throw ValidationException::withMessages([
                'guide1_id' => 'Kursi Pembimbing 1 sudah terisi dan tidak dapat diganti.',
            ]);
        }

        if ($lockedSeats['guide2'] && (int) $data['guide2_id'] !== $lockedSeats['guide2']['id']) {
            throw ValidationException::withMessages([
                'guide2_id' => 'Kursi Pembimbing 2 sudah terisi dan tidak dapat diganti.',
            ]);
        }

        $guide1 = User::find($data['guide1_id']);
        $guide2 = User::find($data['guide2_id']);

        if (! $guide1?->hasRole('dosen') || ! $guide2?->hasRole('dosen')) {
            return back()->withErrors(['guide1_id' => 'Calon pembimbing harus dosen aktif.'])->withInput();
        }

        if ($this->nuirService->hasPendingDuplicateProposal(
            (int) $data['nuir_submission_id'],
            (int) $data['guide1_id'],
            (int) $data['guide2_id'],
        )) {
            return back()->withErrors(['guide2_id' => 'Usulan dengan pasangan dosen yang sama masih pending.'])->withInput();
        }

        $guide1Status = $lockedSeats['guide1'] ? 'accepted' : 'pending';
        $guide2Status = $lockedSeats['guide2'] ? 'accepted' : 'pending';

        if ($guide1Status === 'pending' && ! $this->quotaService->hasQuota($guide1, 1, $submission->year_generation)) {
            throw ValidationException::withMessages([
                'guide1_id' => 'Kuota Pembimbing 1 dosen ini sudah habis.',
            ]);
        }

        if ($guide2Status === 'pending' && ! $this->quotaService->hasQuota($guide2, 2, $submission->year_generation)) {
            throw ValidationException::withMessages([
                'guide2_id' => 'Kuota Pembimbing 2 dosen ini sudah habis.',
            ]);
        }

        if ($guide1Status === 'pending' && $this->needsQuotaConsumption($submission, (int) $data['guide1_id'], 1)) {
            $this->quotaService->consume($guide1, 1, $submission->year_generation);
        }

        if ($guide2Status === 'pending' && $this->needsQuotaConsumption($submission, (int) $data['guide2_id'], 2)) {
            $this->quotaService->consume($guide2, 2, $submission->year_generation);
        }

        NuirProposal::create([
            'nuir_submission_id' => $data['nuir_submission_id'],
            'guide1_id' => $data['guide1_id'],
            'guide2_id' => $data['guide2_id'],
            'guide1_status' => $guide1Status,
            'guide2_status' => $guide2Status,
            'guide1_responded_at' => $lockedSeats['guide1'] ? now() : null,
            'guide2_responded_at' => $lockedSeats['guide2'] ? now() : null,
        ]);

        return to_route('nuir.proposal.index')->with('success', 'Usulan calon pembimbing berhasil diajukan.');
    }

    public function proposeSeat(NuirSubmission $submission, User $user, int $seat, int $guideId): NuirProposal
    {
        if (! in_array($seat, [1, 2], true)) {
            abort(422);
        }

        if ($this->hasFinalProposal($user)) {
            throw ValidationException::withMessages([
                'guide' => 'Pembimbing sudah ditetapkan.',
            ]);
        }

        $guide = User::find($guideId);

        if (! $guide?->hasRole('dosen')) {
            throw ValidationException::withMessages([
                'guide' => 'Calon pembimbing harus dosen aktif.',
            ]);
        }

        $lockedSeats = $submission->lockedSeats();
        $locked = $lockedSeats['guide'.$seat] ?? null;

        if ($locked && (int) $locked['id'] !== $guideId) {
            throw ValidationException::withMessages([
                'guide' => 'Kursi Pembimbing '.$seat.' sudah terisi dan tidak dapat diganti.',
            ]);
        }

        $proposal = NuirProposal::query()
            ->where('nuir_submission_id', $submission->id)
            ->where('final', false)
            ->latest('id')
            ->first();

        $guideColumn = $seat === 1 ? 'guide1_id' : 'guide2_id';
        $statusColumn = $seat === 1 ? 'guide1_status' : 'guide2_status';
        $noteColumn = $seat === 1 ? 'guide1_note' : 'guide2_note';
        $respondedColumn = $seat === 1 ? 'guide1_responded_at' : 'guide2_responded_at';

        if ($proposal && $proposal->{$guideColumn} === $guideId && $proposal->{$statusColumn} === 'pending') {
            return $proposal;
        }

        $otherGuideColumn  = $seat === 1 ? 'guide2_id' : 'guide1_id';
        $otherStatusColumn = $seat === 1 ? 'guide2_status' : 'guide1_status';

        if ($proposal
            && $proposal->{$otherGuideColumn} === $guideId
            && $proposal->{$otherStatusColumn} !== 'rejected'
        ) {
            throw ValidationException::withMessages([
                'guide' => 'Dosen yang sama tidak dapat dipilih untuk kedua kursi pembimbing.',
            ]);
        }

        if (! $locked && ! $this->quotaService->hasQuota($guide, $seat, $submission->year_generation)) {
            throw ValidationException::withMessages([
                'guide' => 'Kuota Pembimbing '.$seat.' dosen ini sudah habis.',
            ]);
        }

        if ($proposal === null) {
            $proposal = NuirProposal::create([
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $seat === 1 ? $guideId : null,
                'guide2_id' => $seat === 2 ? $guideId : null,
                'guide1_status' => $seat === 1 ? 'pending' : 'pending',
                'guide2_status' => $seat === 2 ? 'pending' : 'pending',
            ]);
        } else {
            $proposal->update([
                $guideColumn => $guideId,
                $statusColumn => 'pending',
                $noteColumn => null,
                $respondedColumn => null,
            ]);
        }

        NuirRevisionEvent::create([
            'nuir_submission_id' => $submission->id,
            'submission_version' => $submission->version ?? 1,
            'actor_id'           => $user->id,
            'target_user_id'     => $guideId,
            'actor_role'         => NuirRevisionEvent::ROLE_MAHASISWA,
            'event_type'         => NuirRevisionEvent::TYPE_PROPOSAL_SELECTION,
            'subject'            => 'guide'.$seat,
            'nuir_proposal_id'   => $proposal->id,
            'note'               => '',
            'recorded_at'        => now(),
        ]);

        if (! $locked && $this->needsQuotaConsumption($submission, $guideId, $seat)) {
            $this->quotaService->consume($guide, $seat, $submission->year_generation);
        }

        return $proposal->fresh(['guide1', 'guide2']);
    }

    public function lecturersForSeat(User $user, string $yearGeneration, int $guideOrder, array $lockedSeats): Collection
    {
        if ($guideOrder === 1 && $lockedSeats['guide1']) {
            return User::where('id', $lockedSeats['guide1']['id'])->get();
        }

        if ($guideOrder === 2 && $lockedSeats['guide2']) {
            return User::where('id', $lockedSeats['guide2']['id'])->get();
        }

        return User::role('dosen')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get()
            ->filter(fn (User $lecturer) => $this->quotaService->hasQuota($lecturer, $guideOrder, $yearGeneration))
            ->values();
    }

    /**
     * @return list<array{id: int, name: string, remaining_quota: int, selectable: bool}>
     */
    public function lecturerSeatOptions(User $user, string $yearGeneration, int $guideOrder, array $lockedSeats): array
    {
        if ($guideOrder === 1 && $lockedSeats['guide1']) {
            return $this->singleLecturerSeatOption(
                (int) $lockedSeats['guide1']['id'],
                1,
                $yearGeneration,
                selectable: false,
            );
        }

        if ($guideOrder === 2 && $lockedSeats['guide2']) {
            return $this->singleLecturerSeatOption(
                (int) $lockedSeats['guide2']['id'],
                2,
                $yearGeneration,
                selectable: false,
            );
        }

        $lecturerIds = GuideAllocation::query()
            ->where('year', (int) $yearGeneration)
            ->where('active', true)
            ->pluck('user_id');

        return User::role('dosen')
            ->where('id', '!=', $user->id)
            ->whereIn('id', $lecturerIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $lecturer): array => [
                'id' => $lecturer->id,
                'name' => $lecturer->name,
                'remaining_quota' => $this->quotaService->remainingQuota($lecturer, $guideOrder, $yearGeneration),
                'selectable' => $this->quotaService->hasQuota($lecturer, $guideOrder, $yearGeneration),
            ])
            ->values()
            ->all();
    }

    public function lecturers(User $user, string $yearGeneration, array $lockedSeats = ['guide1' => null, 'guide2' => null]): Collection
    {
        return User::role('dosen')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get()
            ->filter(function (User $lecturer) use ($yearGeneration, $lockedSeats) {
                $p1Ok = $lockedSeats['guide1'] !== null
                    || $this->quotaService->hasQuota($lecturer, 1, $yearGeneration);
                $p2Ok = $lockedSeats['guide2'] !== null
                    || $this->quotaService->hasQuota($lecturer, 2, $yearGeneration);

                return $p1Ok || $p2Ok;
            })
            ->values();
    }

    public function cancelSeat(NuirProposal $proposal, int $seat, User $actor, ?string $note = null, string $actorRole = NuirRevisionEvent::ROLE_MANAJER): void
    {
        if (! in_array($seat, [1, 2], true)) {
            return;
        }

        $guideIdColumn  = $seat === 1 ? 'guide1_id' : 'guide2_id';
        $statusColumn   = $seat === 1 ? 'guide1_status' : 'guide2_status';
        $noteColumn     = $seat === 1 ? 'guide1_note' : 'guide2_note';
        $respondedCol   = $seat === 1 ? 'guide1_responded_at' : 'guide2_responded_at';

        if (! $proposal->{$guideIdColumn}) {
            return;
        }

        $cancelledGuideId = $proposal->{$guideIdColumn};

        $this->releaseSeatQuota($proposal, $seat);

        NuirRevisionEvent::create([
            'nuir_submission_id' => $proposal->nuir_submission_id,
            'submission_version' => $proposal->submission?->version ?? 1,
            'actor_id'           => $actor->id,
            'target_user_id'     => $proposal->{$guideIdColumn},
            'actor_role'         => $actorRole,
            'event_type'         => NuirRevisionEvent::TYPE_PROPOSAL_CANCELLATION,
            'subject'            => 'guide'.$seat,
            'nuir_proposal_id'   => $proposal->id,
            'note'               => $note ?? '',
            'recorded_at'        => now(),
        ]);

        $this->reopenNuiFieldsForCancelledSeat($proposal, $cancelledGuideId, $actor, $actorRole);

        $proposal->update([
            $guideIdColumn => null,
            $statusColumn  => 'pending',
            $noteColumn    => null,
            $respondedCol  => null,
        ]);
    }

    /**
     * When a guide's seat is cancelled after they had already approved some
     * NUI fields, those approvals must be invalidated so the student can edit
     * the fields again for the next candidate guide to review.
     */
    private function reopenNuiFieldsForCancelledSeat(NuirProposal $proposal, int $cancelledGuideId, User $actor, string $actorRole): void
    {
        $note = 'Usulan pembimbing dibatalkan, elemen perlu ditinjau ulang oleh calon pembimbing baru.';

        $approvedFields = NuirContentReview::query()
            ->where('nuir_submission_id', $proposal->nuir_submission_id)
            ->where('user_id', $cancelledGuideId)
            ->where('approved', true)
            ->pluck('field');

        if ($approvedFields->isEmpty()) {
            return;
        }

        NuirContentReview::query()
            ->where('nuir_submission_id', $proposal->nuir_submission_id)
            ->where('user_id', $cancelledGuideId)
            ->where('approved', true)
            ->update([
                'approved' => false,
                'note' => $note,
                'reviewed_at' => now(),
            ]);

        $submission = $proposal->submission;

        foreach ($approvedFields as $field) {
            $this->revisionHistory->logNuiRevision($submission, $actor, $actorRole, $field, $note);
        }
    }

    public function releaseSeatQuota(NuirProposal $proposal, int $guideOrder): void
    {
        $submission = $proposal->submission;
        $lecturerId = $guideOrder === 1 ? $proposal->guide1_id : $proposal->guide2_id;

        if (! $this->needsQuotaRelease($submission, $lecturerId, $guideOrder, $proposal->id)) {
            return;
        }

        $lecturer = User::find($lecturerId);

        if ($lecturer) {
            $this->quotaService->release($lecturer, $guideOrder, $submission->year_generation);
        }
    }

    private function needsQuotaConsumption(NuirSubmission $submission, int $lecturerId, int $guideOrder): bool
    {
        $statusColumn = $guideOrder === 1 ? 'guide1_status' : 'guide2_status';
        $guideColumn = $guideOrder === 1 ? 'guide1_id' : 'guide2_id';

        return ! NuirProposal::where('nuir_submission_id', $submission->id)
            ->where($guideColumn, $lecturerId)
            ->whereIn($statusColumn, ['pending', 'accepted'])
            ->exists();
    }

    private function needsQuotaRelease(NuirSubmission $submission, int $lecturerId, int $guideOrder, int $excludeProposalId): bool
    {
        $statusColumn = $guideOrder === 1 ? 'guide1_status' : 'guide2_status';
        $guideColumn = $guideOrder === 1 ? 'guide1_id' : 'guide2_id';

        return ! NuirProposal::where('nuir_submission_id', $submission->id)
            ->where('id', '!=', $excludeProposalId)
            ->where($guideColumn, $lecturerId)
            ->whereIn($statusColumn, ['pending', 'accepted'])
            ->exists();
    }

    private function hasFinalProposal(User $user): bool
    {
        return NuirProposal::whereHas('submission', fn ($q) => $q->where('user_id', $user->id))
            ->where('final', true)
            ->exists();
    }

    /**
     * @return list<array{id: int, name: string, remaining_quota: int, selectable: bool}>
     */
    private function singleLecturerSeatOption(
        int $lecturerId,
        int $guideOrder,
        string $yearGeneration,
        bool $selectable,
    ): array {
        $lecturer = User::find($lecturerId);

        if (! $lecturer) {
            return [];
        }

        return [[
            'id' => $lecturer->id,
            'name' => $lecturer->name,
            'remaining_quota' => $this->quotaService->remainingQuota($lecturer, $guideOrder, $yearGeneration),
            'selectable' => $selectable,
        ]];
    }
}
