<?php

namespace App\Http\Controllers\Setting\Nuir;

use App\DataTables\NuirSubmissionsDataTable;
use App\Http\Controllers\Controller;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(NuirSubmissionsDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function show(NuirSubmission $nuirSubmission)
    {
        $nuirSubmission->load(['user', 'references', 'dbsReviewer', 'parentSubmission']);
        $setting = NuirSetting::where('year_generation', $nuirSubmission->year_generation)->first();
        $history = $this->parentChain($nuirSubmission);
        $approvedCount = $nuirSubmission->references()->where('ref_approved', true)->count();

        return view('setting.nuir.submission-show', compact('nuirSubmission', 'setting', 'history', 'approvedCount'));
    }

    public function reviewReference(Request $request, NuirReference $nuirReference)
    {
        $approved = $request->boolean('ref_approved');

        $data = $request->validate([
            'ref_note' => [$approved ? 'nullable' : 'required', 'string'],
        ]);

        $nuirReference->update([
            'ref_approved' => $approved,
            'ref_note' => $approved ? null : ($data['ref_note'] ?? null),
        ]);

        return back()->with('success', 'Keputusan referensi disimpan.');
    }

    public function review(Request $request, NuirSubmission $nuirSubmission)
    {
        $data = $request->validate([
            'action' => ['required', 'in:content_ok,revision'],
            'dbs_note' => ['nullable', 'string', 'required_if:action,revision'],
        ]);

        if ($data['action'] === 'content_ok') {
            $setting = NuirSetting::where('year_generation', $nuirSubmission->year_generation)->first();
            $min = $setting?->min_references_approved ?? 10;
            $approved = $nuirSubmission->references()->where('ref_approved', true)->count();

            if ($approved < $min) {
                return back()->with('warning', "Minimal {$min} referensi harus disetujui sebelum konten disetujui.");
            }
        }

        $nuirSubmission->update([
            'status' => $data['action'] === 'content_ok' ? 'content_ok' : 'revision',
            'dbs_note' => $data['dbs_note'] ?? null,
            'dbs_reviewer_id' => auth()->id(),
            'dbs_reviewed_at' => now(),
        ]);

        return to_route('nuir.review.show', $nuirSubmission)->with('success', 'Review submission disimpan.');
    }

    private function parentChain(NuirSubmission $submission)
    {
        $chain = collect();
        $current = $submission->parentSubmission;

        while ($current) {
            $chain->push($current->load('dbsReviewer'));
            $current = $current->parentSubmission;
        }

        return $chain;
    }
}
