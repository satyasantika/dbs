<?php

namespace App\Http\Controllers\Selection;

use App\Filament\Mahasiswa\Pages\CreateNuirProposal;
use App\Filament\Mahasiswa\Pages\NuirProposalOverview;
use App\Http\Controllers\Controller;
use App\Services\NuirProposalService;
use Illuminate\Http\Request;

class NuirProposalController extends Controller
{
    public function __construct(private NuirProposalService $proposalService)
    {
    }

    public function index()
    {
        return redirect(NuirProposalOverview::getUrl(panel: 'mahasiswa'));
    }

    public function create()
    {
        $result = $this->proposalService->createFormData(auth()->user());

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return redirect(CreateNuirProposal::getUrl(panel: 'mahasiswa'));
    }

    public function store(Request $request)
    {
        return $this->proposalService->store($request, auth()->user());
    }
}
