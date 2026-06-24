<?php

namespace App\Services;

use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class NuirProposalService
{
    private const PROPOSABLE_STATUSES = ['submitted', 'revision', 'content_ok'];

    public function __construct(private NuirService $nuirService)
    {
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

        return [
            'submission' => $submission,
            'previousRejected' => $previousRejected,
            'lecturers' => $this->lecturers($user),
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

        NuirProposal::create([
            'nuir_submission_id' => $data['nuir_submission_id'],
            'guide1_id' => $data['guide1_id'],
            'guide2_id' => $data['guide2_id'],
        ]);

        return to_route('nuir.proposal.index')->with('success', 'Usulan calon pembimbing berhasil diajukan.');
    }

    public function lecturers(User $user): Collection
    {
        return User::role('dosen')->where('id', '!=', $user->id)->orderBy('name')->get();
    }

    private function hasFinalProposal(User $user): bool
    {
        return NuirProposal::whereHas('submission', fn ($q) => $q->where('user_id', $user->id))
            ->where('final', true)
            ->exists();
    }
}
