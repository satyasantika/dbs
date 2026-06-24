<?php

namespace App\Http\Controllers\Selection;

use App\Http\Controllers\Controller;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirService;
use Illuminate\Http\Request;

class NuirSubmissionController extends Controller
{
    public function __construct(private NuirService $nuirService)
    {
    }

    public function index()
    {
        $user = auth()->user();
        $setting = $this->nuirService->getActiveSetting($user);

        if (! $setting || ! $setting->active) {
            return view('selection.nuir.index', [
                'setting' => $setting,
                'submission' => null,
                'versions' => collect(),
                'closed' => true,
                'stage3' => false,
            ]);
        }

        if ($setting->stage === 3) {
            return view('selection.nuir.index', [
                'setting' => $setting,
                'submission' => null,
                'versions' => collect(),
                'closed' => false,
                'stage3' => true,
            ]);
        }

        $submission = $this->nuirService->activeSubmission($user);
        $versions = $this->versionChain($user);

        return view('selection.nuir.index', [
            'setting' => $setting,
            'submission' => $submission,
            'versions' => $versions,
            'closed' => false,
            'stage3' => false,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $setting = $this->requireWritableSetting($user);

        if ($this->nuirService->hasFinalizedSubmission($user)) {
            return to_route('nuir.submission.index')->with('info', 'Pembimbing Anda sudah ditetapkan.');
        }

        if ($response = $this->deadlineResponse($setting)) {
            return $response;
        }

        if ($this->nuirService->activeSubmission($user)) {
            abort(403);
        }

        return view('selection.nuir.form', [
            'setting' => $setting,
            'submission' => new NuirSubmission(),
            'stage' => $setting->stage,
            'rejectedRefs' => [],
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $setting = $this->requireWritableSetting($user);

        if ($this->nuirService->hasFinalizedSubmission($user)) {
            return to_route('nuir.submission.index')->with('info', 'Pembimbing Anda sudah ditetapkan.');
        }

        if ($response = $this->deadlineResponse($setting)) {
            return $response;
        }

        if ($this->nuirService->activeSubmission($user)) {
            abort(403);
        }

        if ($setting->stage === 2) {
            $data = $request->validate(['title' => ['required', 'string']]);
            NuirSubmission::create([
                'user_id' => $user->id,
                'year_generation' => $setting->year_generation,
                'title' => $data['title'],
                'status' => 'content_ok',
            ]);

            return to_route('nuir.proposal.create')->with('success', 'Judul tersimpan. Lanjutkan proposal pembimbing.');
        }

        $data = $this->validateSubmission($request, $setting);

        $submission = NuirSubmission::create([
            'user_id' => $user->id,
            'year_generation' => $setting->year_generation,
            'title' => $data['title'],
            'novelty' => $data['novelty'],
            'urgency' => $data['urgency'],
            'impact' => $data['impact'],
            'status' => 'draft',
        ]);

        $this->syncReferences($submission, $request->input('references', []));

        return to_route('nuir.submission.index')->with('success', 'Draft NUIR berhasil disimpan.');
    }

    public function edit(NuirSubmission $nuirSubmission)
    {
        $this->authorizeSubmission($nuirSubmission, editable: true);

        return view('selection.nuir.form', [
            'setting' => $this->requireStageOneSetting(auth()->user()),
            'submission' => $nuirSubmission->load('references'),
            'stage' => 1,
            'rejectedRefs' => [],
        ]);
    }

    public function update(Request $request, NuirSubmission $nuirSubmission)
    {
        $this->authorizeSubmission($nuirSubmission, editable: true);
        $setting = $this->requireStageOneSetting(auth()->user());
        $data = $this->validateSubmission($request, $setting);

        $nuirSubmission->update([
            'title' => $data['title'],
            'novelty' => $data['novelty'],
            'urgency' => $data['urgency'],
            'impact' => $data['impact'],
        ]);

        $this->syncReferences($nuirSubmission, $request->input('references', []));

        return to_route('nuir.submission.index')->with('success', 'Draft NUIR berhasil diperbarui.');
    }

    public function submit(NuirSubmission $nuirSubmission)
    {
        $this->authorizeSubmission($nuirSubmission, status: 'draft');
        $setting = $this->nuirService->getActiveSetting(auth()->user());

        if ($setting && ! $this->nuirService->checkDeadline($setting)) {
            return back()->with('warning', 'Batas pengajuan NUIR telah berakhir.');
        }

        if ($setting?->stage === 2) {
            return to_route('nuir.proposal.create');
        }

        $nuirSubmission->update(['status' => 'submitted']);

        return to_route('nuir.submission.index')->with('success', 'NUIR berhasil diajukan ke DBS.');
    }

    public function createRevision(NuirSubmission $nuirSubmission)
    {
        $this->authorizeRevision($nuirSubmission);
        $setting = $this->requireStageOneSetting(auth()->user());
        $rejectedRefs = $nuirSubmission->references()
            ->where('ref_approved', false)
            ->pluck('ref_note', 'ref_order')
            ->all();

        return view('selection.nuir.form', [
            'setting' => $setting,
            'submission' => $nuirSubmission->load('references'),
            'stage' => 1,
            'rejectedRefs' => $rejectedRefs,
            'revisionParent' => $nuirSubmission,
        ]);
    }

    public function storeRevision(Request $request, NuirSubmission $nuirSubmission)
    {
        $this->authorizeRevision($nuirSubmission);
        $setting = $this->requireStageOneSetting(auth()->user());

        if ($response = $this->deadlineResponse($setting)) {
            return $response;
        }

        $data = $this->validateSubmission($request, $setting);

        $newSubmission = NuirSubmission::create([
            'user_id' => auth()->id(),
            'year_generation' => $nuirSubmission->year_generation,
            'parent_submission_id' => $nuirSubmission->id,
            'version' => $nuirSubmission->version + 1,
            'title' => $data['title'],
            'novelty' => $data['novelty'],
            'urgency' => $data['urgency'],
            'impact' => $data['impact'],
            'status' => 'draft',
        ]);

        $this->syncReferences($newSubmission, $request->input('references', []));

        return to_route('nuir.submission.index')->with('success', 'Revisi NUIR berhasil dibuat.');
    }

    private function authorizeRevision(NuirSubmission $submission): void
    {
        if ($submission->user_id !== auth()->id() || $submission->status !== 'revision') {
            abort(403);
        }

        if (NuirSubmission::where('parent_submission_id', $submission->id)->exists()) {
            abort(403);
        }
    }

    private function requireStageOneSetting(User $user): NuirSetting
    {
        $setting = $this->nuirService->getActiveSetting($user);

        if (! $setting || $setting->stage !== 1 || ! $setting->active) {
            abort(403);
        }

        return $setting;
    }

    private function requireWritableSetting(User $user): NuirSetting
    {
        $setting = $this->nuirService->getActiveSetting($user);

        if (! $setting || ! $setting->active || $setting->stage === 3) {
            abort(403);
        }

        return $setting;
    }

    private function authorizeSubmission(
        NuirSubmission $submission,
        bool $editable = false,
        ?string $status = null,
    ): void {
        if ($submission->user_id !== auth()->id()) {
            abort(403);
        }

        if ($editable && ! $submission->isEditable()) {
            abort(403);
        }

        if ($status !== null && $submission->status !== $status) {
            abort(403);
        }
    }

    private function validateSubmission(Request $request, NuirSetting $setting): array
    {
        $rules = [
            'title' => ['required', 'string'],
            'novelty' => ['required', 'string'],
            'urgency' => ['required', 'string'],
            'impact' => ['required', 'string'],
        ];

        foreach (['novelty' => 'max_chars_novelty', 'urgency' => 'max_chars_urgency', 'impact' => 'max_chars_impact'] as $field => $limitField) {
            if ($setting->{$limitField}) {
                $rules[$field][] = 'max:'.$setting->{$limitField};
            }
        }

        return $request->validate($rules);
    }

    private function syncReferences(NuirSubmission $submission, array $references): void
    {
        $orders = [];

        foreach ($references as $order => $ref) {
            $order = (int) $order;
            if ($order < 1 || $order > 10) {
                continue;
            }

            if (! $this->referenceFilled($ref)) {
                continue;
            }

            NuirReference::updateOrCreate(
                [
                    'nuir_submission_id' => $submission->id,
                    'ref_order' => $order,
                ],
                [
                    'link_ojs' => $ref['link_ojs'] ?? null,
                    'indexer_name' => $ref['indexer_name'] ?? null,
                    'link_index' => $ref['link_index'] ?? null,
                    'link_drive' => $ref['link_drive'] ?? null,
                    'quote' => $ref['quote'] ?? null,
                    'relevance' => $ref['relevance'] ?? null,
                ]
            );

            $orders[] = $order;
        }

        if ($orders !== []) {
            $submission->references()->whereNotIn('ref_order', $orders)->delete();
        }
    }

    private function referenceFilled(array $ref): bool
    {
        return collect($ref)->filter(fn ($value) => filled($value))->isNotEmpty();
    }

    private function versionChain(User $user)
    {
        return NuirSubmission::where('user_id', $user->id)
            ->orderByDesc('version')
            ->get();
    }

    private function deadlineResponse(NuirSetting $setting)
    {
        if (! $this->nuirService->checkDeadline($setting)) {
            return back()->with('warning', 'Batas pengajuan NUIR telah berakhir.');
        }

        return null;
    }
}
