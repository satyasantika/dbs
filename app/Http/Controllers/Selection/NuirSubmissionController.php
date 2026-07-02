<?php

namespace App\Http\Controllers\Selection;

use App\Filament\Mahasiswa\Pages\CreateNuirProposal;
use App\Filament\Mahasiswa\Pages\CreateNuirSubmission;
use App\Filament\Mahasiswa\Pages\EditNuirSubmission;
use App\Filament\Mahasiswa\Pages\NuirProposalOverview;
use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Filament\Mahasiswa\Pages\ReviseNuirSubmission;
use App\Http\Controllers\Controller;
use App\Models\NuirSubmission;
use App\Services\NuirSubmissionService;
use Illuminate\Http\Request;

class NuirSubmissionController extends Controller
{
    public function __construct(private NuirSubmissionService $submissionService)
    {
    }

    public function index()
    {
        return redirect(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'));
    }

    public function create()
    {
        $result = $this->submissionService->createFormData(auth()->user());

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return redirect(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'));
    }

    public function store(Request $request)
    {
        return $this->submissionService->store($request, auth()->user());
    }

    public function edit(NuirSubmission $nuirSubmission)
    {
        $this->submissionService->editFormData(auth()->user(), $nuirSubmission);

        return redirect(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'));
    }

    public function update(Request $request, NuirSubmission $nuirSubmission)
    {
        return $this->submissionService->update($request, $nuirSubmission, auth()->user());
    }

    public function submit(NuirSubmission $nuirSubmission)
    {
        return $this->submissionService->submit($nuirSubmission, auth()->user());
    }

    public function createRevision(NuirSubmission $nuirSubmission)
    {
        $this->submissionService->revisionFormData(auth()->user(), $nuirSubmission);

        return redirect(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'));
    }

    public function storeRevision(Request $request, NuirSubmission $nuirSubmission)
    {
        return $this->submissionService->storeRevision($request, $nuirSubmission, auth()->user());
    }
}
