<?php

namespace App\Http\Controllers\Selection;

use App\Http\Controllers\Controller;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NuirProposalController extends Controller
{
    public function __construct(private NuirService $nuirService)
    {
    }

    public function index()
    {
        $proposals = NuirProposal::with(['submission', 'guide1', 'guide2'])
            ->whereHas('submission', fn ($q) => $q->where('user_id', auth()->id()))
            ->latest()
            ->get();

        $finalProposal = $proposals->firstWhere('final', true);
        $contentOkSubmission = NuirSubmission::where('user_id', auth()->id())
            ->where('status', 'content_ok')
            ->latest('id')
            ->first();

        return view('selection.nuir.proposal-index', compact('proposals', 'finalProposal', 'contentOkSubmission'));
    }

    public function create()
    {
        $setting = $this->nuirService->getActiveSetting(auth()->user());

        if (! $setting || $setting->stage === 3) {
            abort(403);
        }

        if ($this->hasFinalProposal()) {
            return to_route('nuir.proposal.index')->with('warning', 'Pembimbing sudah ditetapkan.');
        }

        $submission = NuirSubmission::where('user_id', auth()->id())
            ->where('status', 'content_ok')
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

        $lecturers = User::role('dosen')->where('id', '!=', auth()->id())->orderBy('name')->get();

        return view('selection.nuir.proposal-form', compact('submission', 'previousRejected', 'lecturers'));
    }

    public function store(Request $request)
    {
        if ($this->hasFinalProposal()) {
            return to_route('nuir.proposal.index')->with('warning', 'Pembimbing sudah ditetapkan.');
        }

        $data = $request->validate([
            'nuir_submission_id' => [
                'required',
                Rule::exists('nuir_submissions', 'id')->where(
                    fn ($q) => $q->where('user_id', auth()->id())->where('status', 'content_ok')
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
            return back()->withErrors(['guide2_id' => 'Proposal dengan pasangan dosen yang sama masih pending.'])->withInput();
        }

        NuirProposal::create([
            'nuir_submission_id' => $data['nuir_submission_id'],
            'guide1_id' => $data['guide1_id'],
            'guide2_id' => $data['guide2_id'],
        ]);

        return to_route('nuir.proposal.index')->with('success', 'Proposal berhasil diajukan.');
    }

    private function hasFinalProposal(): bool
    {
        return NuirProposal::whereHas('submission', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('final', true)
            ->exists();
    }
}
