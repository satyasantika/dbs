<?php

namespace App\Http\Controllers\Setting\Nuir;

use App\DataTables\NuirProposalsDataTable;
use App\DataTables\NuirSubmissionsDataTable;
use App\Http\Controllers\Controller;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirReviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    public function __construct(private NuirReviewService $reviewService)
    {
    }

    public function index(NuirSubmissionsDataTable $dataTable)
    {
        return redirect(\App\Filament\Dbs\Resources\NuirSubmissionResource::getUrl('index', panel: 'dbs'));
    }

    public function show(NuirSubmission $nuirSubmission)
    {
        return redirect(\App\Filament\Dbs\Resources\NuirSubmissionResource::getUrl('view', [
            'record' => $nuirSubmission,
        ], panel: 'dbs'));
    }

    public function reviewReference(Request $request, NuirReference $nuirReference)
    {
        try {
            $this->reviewService->reviewReference(
                $nuirReference,
                $request->boolean('ref_approved'),
                $request->input('ref_note'),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Keputusan referensi disimpan.');
    }

    public function review(Request $request, NuirSubmission $nuirSubmission)
    {
        $data = $request->validate([
            'action' => ['required', 'in:content_ok,revision'],
            'dbs_note' => ['nullable', 'string', 'required_if:action,revision'],
        ]);

        try {
            $this->reviewService->reviewSubmission(
                $nuirSubmission,
                $data['action'],
                $data['dbs_note'] ?? null,
            );
        } catch (ValidationException $exception) {
            if ($exception->errors()['action'][0] ?? null) {
                return back()->with('warning', $exception->errors()['action'][0]);
            }

            throw $exception;
        }

        return to_route('nuir.review.show', $nuirSubmission)->with('success', 'Review submission disimpan.');
    }

    public function proposals(NuirProposalsDataTable $dataTable)
    {
        return redirect(\App\Filament\Dbs\Resources\NuirProposalResource::getUrl('index', panel: 'dbs'));
    }

    public function forceFinalize(NuirProposal $nuirProposal)
    {
        $this->reviewService->forceFinalize($nuirProposal);

        return back()->with('success', 'Usulan calon pembimbing berhasil di-finalize.');
    }
}
