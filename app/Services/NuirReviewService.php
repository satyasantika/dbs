<?php

namespace App\Services;

use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirRevisionHistoryService;
use App\Support\NuirReferenceRevisionFields;
use Illuminate\Validation\ValidationException;

class NuirReviewService
{
    public function __construct(
        private NuirService $nuirService,
        private NuirRevisionHistoryService $revisionHistory,
    ) {
    }

    public function reviewReference(
        NuirReference $reference,
        bool $approved,
        ?string $note = null,
        bool $recordHistory = true,
        ?array $revisionFields = null,
    ): void {
        if (! $approved && blank($note)) {
            throw ValidationException::withMessages([
                'ref_note' => 'Catatan wajib diisi saat meminta revisi referensi.',
            ]);
        }

        $normalizedFields = NuirReferenceRevisionFields::normalize($revisionFields);

        if (! $approved && $recordHistory && auth()->user()) {
            $this->revisionHistory->logReferenceRevision(
                $reference,
                auth()->user(),
                NuirRevisionEvent::ROLE_DBS,
                $note,
                $normalizedFields ?: null,
            );
        }

        $reference->update([
            'ref_approved' => $approved,
            'ref_note' => $approved ? null : $note,
            'ref_revision_fields' => $approved ? null : ($normalizedFields ?: null),
        ]);
    }

    public function reviewSubmission(NuirSubmission $submission, string $action, ?string $dbsNote = null): void
    {
        if ($action === 'revision' && blank($dbsNote)) {
            throw ValidationException::withMessages([
                'dbs_note' => 'Catatan revisi wajib diisi.',
            ]);
        }

        if ($action === 'content_ok') {
            $setting = NuirSetting::where('year_generation', $submission->year_generation)->first();
            $min = $setting?->min_references_approved ?? 10;
            $approved = $submission->references()->where('ref_approved', true)->count();

            if ($approved < $min) {
                throw ValidationException::withMessages([
                    'action' => "Minimal {$min} referensi harus disetujui sebelum konten disetujui.",
                ]);
            }
        }

        if ($action === 'revision' && auth()->user()) {
            $this->revisionHistory->logDbsRevision($submission, auth()->user(), $dbsNote);
        }

        $submission->update([
            'status' => $action === 'content_ok' ? 'content_ok' : 'revision',
            'dbs_note' => $dbsNote,
            'dbs_reviewer_id' => auth()->id(),
            'dbs_reviewed_at' => now(),
        ]);
    }

    public function forceFinalize(NuirProposal $proposal): void
    {
        if ($proposal->guide1_status !== 'accepted' || $proposal->guide2_status !== 'accepted') {
            abort(403);
        }

        $this->nuirService->finalizeProposal($proposal->fresh());
    }
}
