<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSubmission;
use App\Services\NuirAssignmentService;
use App\Services\NuirProposalService;
use App\Services\NuirService;
use Illuminate\Http\Request;

class NuirProposalController extends Controller
{
    public function __construct(
        private NuirService $nuirService,
        private NuirAssignmentService $assignmentService,
        private NuirProposalService $proposalService,
    ) {
    }

    public function index()
    {
        $userId = auth()->id();
        $proposals = NuirProposal::with(['submission.user', 'guide1', 'guide2'])
            ->where(function ($query) use ($userId) {
                $query->where('guide1_id', $userId)->orWhere('guide2_id', $userId);
            })
            ->latest()
            ->get();

        $pending = $proposals->filter(fn (NuirProposal $p) => $this->pendingForUser($p));
        $responded = $proposals->reject(fn (NuirProposal $p) => $this->pendingForUser($p));

        return view('dosen.nuir.proposal-index', compact('pending', 'responded'));
    }

    public function show(NuirProposal $nuirProposal)
    {
        $this->authorizeProposal($nuirProposal);
        $nuirProposal->load(['submission.user', 'submission.references.reviews', 'submission.contentReviews', 'guide1', 'guide2']);
        $user = auth()->user();

        return view('dosen.nuir.proposal-show', [
            'proposal' => $nuirProposal,
            'canRespond' => $this->canRespond($nuirProposal),
            'canAcceptProposal' => $this->assignmentService->guideCanAcceptProposal($nuirProposal, $user),
            'canReviewReferences' => $this->canRespond($nuirProposal),
        ]);
    }

    public function accept(NuirProposal $nuirProposal)
    {
        $this->authorizeProposal($nuirProposal);
        $this->ensureCanRespond($nuirProposal);

        if (! $this->assignmentService->guideCanAcceptProposal($nuirProposal, auth()->user())) {
            return back()->with('warning', 'Persetujuan pembimbing hanya dapat diberikan setelah NUIR disetujui final (content_ok).');
        }

        if (auth()->id() === $nuirProposal->guide1_id) {
            $nuirProposal->update([
                'guide1_status' => 'accepted',
                'guide1_responded_at' => now(),
            ]);
        } else {
            $nuirProposal->update([
                'guide2_status' => 'accepted',
                'guide2_responded_at' => now(),
            ]);
        }

        if ($nuirProposal->fresh()->isBothAccepted()) {
            $this->nuirService->finalizeProposal($nuirProposal->fresh());
        }

        return to_route('nuir.dosen.index')->with('success', 'Usulan calon pembimbing diterima.');
    }

    public function reject(Request $request, NuirProposal $nuirProposal)
    {
        $this->authorizeProposal($nuirProposal);
        $this->ensureCanRespond($nuirProposal);

        $data = $request->validate(['note' => ['required', 'string']]);

        if (auth()->id() === $nuirProposal->guide1_id) {
            $nuirProposal->update([
                'guide1_status' => 'rejected',
                'guide1_note' => $data['note'],
                'guide1_responded_at' => now(),
            ]);
            $this->proposalService->releaseSeatQuota($nuirProposal->fresh(), 1);
        } else {
            $nuirProposal->update([
                'guide2_status' => 'rejected',
                'guide2_note' => $data['note'],
                'guide2_responded_at' => now(),
            ]);
            $this->proposalService->releaseSeatQuota($nuirProposal->fresh(), 2);
        }

        return to_route('nuir.dosen.index')->with('success', 'Usulan calon pembimbing ditolak.');
    }

    public function reviewReference(Request $request, NuirProposal $nuirProposal, NuirReference $nuirReference)
    {
        $this->authorizeProposal($nuirProposal);
        $this->ensureCanRespond($nuirProposal);

        if ($nuirReference->nuir_submission_id !== $nuirProposal->nuir_submission_id) {
            abort(404);
        }

        $data = $request->validate([
            'approved' => ['required', 'in:0,1'],
            'note' => ['nullable', 'string', 'required_if:approved,0'],
        ]);

        $approved = (bool) (int) $data['approved'];

        $this->assignmentService->reviewReferenceAsGuide(
            $nuirReference,
            $nuirProposal,
            auth()->user(),
            $approved,
            $data['note'] ?? null,
        );

        return back()->with('success', 'Review referensi disimpan.');
    }

    public function reviewContent(Request $request, NuirProposal $nuirProposal)
    {
        $this->authorizeProposal($nuirProposal);
        $this->ensureCanRespond($nuirProposal);

        $data = $request->validate([
            'field' => ['required', 'in:novelty,urgency,impact'],
            'approved' => ['required', 'in:0,1'],
            'note' => ['nullable', 'string', 'required_if:approved,0'],
        ]);

        $approved = (bool) (int) $data['approved'];

        $this->assignmentService->reviewContentAsGuide(
            $nuirProposal->submission,
            $nuirProposal,
            auth()->user(),
            $data['field'],
            $approved,
            $data['note'] ?? null,
        );

        return back()->with('success', 'Review konten NUIR disimpan.');
    }

    private function authorizeProposal(NuirProposal $proposal): void
    {
        $userId = auth()->id();
        if ($proposal->guide1_id !== $userId && $proposal->guide2_id !== $userId) {
            abort(403);
        }
    }

    private function canRespond(NuirProposal $proposal): bool
    {
        return $this->pendingForUser($proposal);
    }

    private function ensureCanRespond(NuirProposal $proposal): void
    {
        if (! $this->canRespond($proposal)) {
            abort(403);
        }
    }

    private function pendingForUser(NuirProposal $proposal): bool
    {
        if (auth()->id() === $proposal->guide1_id) {
            return $proposal->guide1_status === 'pending';
        }

        if (auth()->id() === $proposal->guide2_id) {
            return $proposal->guide2_status === 'pending';
        }

        return false;
    }
}
