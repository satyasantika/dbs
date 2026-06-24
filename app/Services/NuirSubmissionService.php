<?php

namespace App\Services;

use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NuirSubmissionService
{
    public function __construct(private NuirService $nuirService)
    {
    }

    public function getIndexData(User $user): array
    {
        $setting = $this->nuirService->getActiveSetting($user);

        if (! $setting || ! $setting->active) {
            return [
                'setting' => $setting,
                'submission' => null,
                'versions' => collect(),
                'closed' => true,
                'stage3' => false,
            ];
        }

        if ($setting->stage === 3) {
            return [
                'setting' => $setting,
                'submission' => null,
                'versions' => collect(),
                'closed' => false,
                'stage3' => true,
            ];
        }

        return [
            'setting' => $setting,
            'submission' => $this->nuirService->activeSubmission($user),
            'versions' => $this->versionChain($user),
            'closed' => false,
            'stage3' => false,
        ];
    }

    public function createFormData(User $user): array|RedirectResponse
    {
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

        return [
            'setting' => $setting,
            'submission' => new NuirSubmission(),
            'stage' => $setting->stage,
            'rejectedRefs' => [],
            'revisionParent' => null,
        ];
    }

    public function editFormData(User $user, NuirSubmission $submission): array
    {
        $this->authorizeSubmission($submission, editable: true);

        return [
            'setting' => $this->requireStageOneSetting($user),
            'submission' => $submission->load('references'),
            'stage' => 1,
            'rejectedRefs' => [],
            'revisionParent' => null,
        ];
    }

    public function revisionFormData(User $user, NuirSubmission $submission): array
    {
        $this->authorizeRevision($submission);
        $rejectedRefs = $submission->references()
            ->where('ref_approved', false)
            ->pluck('ref_note', 'ref_order')
            ->all();

        return [
            'setting' => $this->requireStageOneSetting($user),
            'submission' => $submission->load('references'),
            'stage' => 1,
            'rejectedRefs' => $rejectedRefs,
            'revisionParent' => $submission,
        ];
    }

    public function store(Request $request, User $user): RedirectResponse
    {
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

            return to_route('nuir.proposal.create')->with('success', 'Judul tersimpan. Lanjutkan usulan calon pembimbing.');
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

    public function update(Request $request, NuirSubmission $submission, User $user): RedirectResponse
    {
        $this->authorizeSubmission($submission, editable: true);
        $setting = $this->requireStageOneSetting($user);
        $data = $this->validateSubmission($request, $setting);

        $submission->update([
            'title' => $data['title'],
            'novelty' => $data['novelty'],
            'urgency' => $data['urgency'],
            'impact' => $data['impact'],
        ]);

        $this->syncReferences($submission, $request->input('references', []));

        return to_route('nuir.submission.index')->with('success', 'Draft NUIR berhasil diperbarui.');
    }

    public function submit(NuirSubmission $submission, User $user): RedirectResponse
    {
        $this->authorizeSubmission($submission, status: 'draft');
        $setting = $this->nuirService->getActiveSetting($user);

        if ($setting && ! $this->nuirService->checkDeadline($setting)) {
            return back()->with('warning', 'Batas pengajuan NUIR telah berakhir.');
        }

        if ($setting?->stage === 2) {
            return to_route('nuir.proposal.create');
        }

        $submission->update(['status' => 'submitted']);

        return to_route('nuir.submission.index')->with('success', 'NUIR berhasil diajukan ke DBS.');
    }

    public function storeRevision(Request $request, NuirSubmission $parent, User $user): RedirectResponse
    {
        $this->authorizeRevision($parent);
        $setting = $this->requireStageOneSetting($user);

        if ($response = $this->deadlineResponse($setting)) {
            return $response;
        }

        $data = $this->validateSubmission($request, $setting);

        $newSubmission = NuirSubmission::create([
            'user_id' => $user->id,
            'year_generation' => $parent->year_generation,
            'parent_submission_id' => $parent->id,
            'version' => $parent->version + 1,
            'title' => $data['title'],
            'novelty' => $data['novelty'],
            'urgency' => $data['urgency'],
            'impact' => $data['impact'],
            'status' => 'draft',
        ]);

        $this->syncReferences($newSubmission, $request->input('references', []));

        return to_route('nuir.submission.index')->with('success', 'Revisi NUIR berhasil dibuat.');
    }

    public function authorizeRevision(NuirSubmission $submission): void
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

    private function deadlineResponse(NuirSetting $setting): ?RedirectResponse
    {
        if (! $this->nuirService->checkDeadline($setting)) {
            return back()->with('warning', 'Batas pengajuan NUIR telah berakhir.');
        }

        return null;
    }
}
