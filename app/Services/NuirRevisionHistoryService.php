<?php

namespace App\Services;

use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Support\NuirReferenceRevisionFields;
use Illuminate\Support\Collection;

class NuirRevisionHistoryService
{
    public function logNuiRevision(
        NuirSubmission $submission,
        User $actor,
        string $role,
        string $field,
        string $note,
    ): NuirRevisionEvent {
        return $this->record($submission, $actor, $role, NuirRevisionEvent::TYPE_NUI_REVISION, $field, $note);
    }

    public function logReferenceRevision(
        NuirReference $reference,
        User $actor,
        string $role,
        string $note,
        ?array $revisionFields = null,
    ): NuirRevisionEvent {
        return $this->record(
            $reference->submission,
            $actor,
            $role,
            NuirRevisionEvent::TYPE_REFERENCE_REVISION,
            (string) $reference->ref_order,
            $note,
            refOrder: $reference->ref_order,
            revisionFields: $revisionFields,
        );
    }

    public function logProposalRejection(
        NuirProposal $proposal,
        User $actor,
        int $guideOrder,
        string $note,
    ): NuirRevisionEvent {
        $role = $guideOrder === 1 ? NuirRevisionEvent::ROLE_GUIDE1 : NuirRevisionEvent::ROLE_GUIDE2;

        return $this->record(
            $proposal->submission,
            $actor,
            $role,
            NuirRevisionEvent::TYPE_PROPOSAL_REJECTION,
            'guide'.$guideOrder,
            $note,
            proposalId: $proposal->id,
        );
    }

    public function logProposalAcceptance(
        NuirProposal $proposal,
        User $actor,
        int $guideOrder,
    ): NuirRevisionEvent {
        $role = $guideOrder === 1 ? NuirRevisionEvent::ROLE_GUIDE1 : NuirRevisionEvent::ROLE_GUIDE2;

        return $this->record(
            $proposal->submission,
            $actor,
            $role,
            NuirRevisionEvent::TYPE_PROPOSAL_ACCEPTANCE,
            'guide'.$guideOrder,
            'Menyetujui seluruh elemen NUI (Judul, Novelty, Urgency, Impact).',
            proposalId: $proposal->id,
        );
    }

    public function logDbsRevision(NuirSubmission $submission, User $actor, string $note): NuirRevisionEvent
    {
        return $this->record(
            $submission,
            $actor,
            NuirRevisionEvent::ROLE_DBS,
            NuirRevisionEvent::TYPE_DBS_REVISION,
            'submission',
            $note,
        );
    }

    /**
     * @return list<int>
     */
    public function versionLineageIds(NuirSubmission $submission): array
    {
        $ids = [];
        $current = $submission->loadMissing('parentSubmission');

        while ($current !== null) {
            $ids[] = $current->id;
            $current = $current->parentSubmission;
        }

        return $ids;
    }

    public function historyForLineage(NuirSubmission $submission): Collection
    {
        $ids = $this->versionLineageIds($submission);

        return NuirRevisionEvent::query()
            ->with(['actor', 'submission'])
            ->whereIn('nuir_submission_id', $ids)
            ->orderByDesc('recorded_at')
            ->get();
    }

    public function rejectionHistoryForSubmission(NuirSubmission $submission): Collection
    {
        $ids = $this->versionLineageIds($submission);

        return NuirRevisionEvent::query()
            ->with(['actor', 'proposal.guide1', 'proposal.guide2'])
            ->whereIn('nuir_submission_id', $ids)
            ->where('event_type', NuirRevisionEvent::TYPE_PROPOSAL_REJECTION)
            ->orderByDesc('recorded_at')
            ->get();
    }

    /**
     * @return Collection<int, array{heading: string, recorded_at: \Illuminate\Support\Carbon|null, actor_name: string|null, actor_role: string, note: string|null, content: string|null, kind: string}>
     */
    public function contentFieldHistory(NuirSubmission $submission, string $field): Collection
    {
        $lineageIds = $this->versionLineageIds($submission);
        $items = collect();

        NuirSubmission::query()
            ->with('user')
            ->whereIn('id', $lineageIds)
            ->where('id', '!=', $submission->id)
            ->orderByDesc('version')
            ->get()
            ->each(function (NuirSubmission $ancestor) use ($items, $field): void {
                $text = $ancestor->{$field};

                if (blank($text)) {
                    return;
                }

                $items->push($this->enrichHistoryItem([
                    'heading' => 'Isi sebelumnya',
                    'recorded_at' => $ancestor->dbs_reviewed_at ?? $ancestor->updated_at,
                    'actor_name' => $ancestor->user?->name,
                    'actor_role' => 'mahasiswa',
                    'note' => $ancestor->dbs_note,
                    'content' => $text,
                    'kind' => 'snapshot',
                    'submission_version' => $ancestor->version,
                ]));
            });

        $eventQuery = NuirRevisionEvent::query()
            ->with('actor')
            ->whereIn('nuir_submission_id', $lineageIds);

        if ($field === 'title') {
            // Title approval depends on NUI — include all NUI revision events plus any DBS events
            $eventQuery->where(function ($query): void {
                $query->where('event_type', NuirRevisionEvent::TYPE_NUI_REVISION)
                    ->orWhere('event_type', NuirRevisionEvent::TYPE_DBS_REVISION);
            });
        } else {
            $eventQuery->where(function ($query) use ($field): void {
                $query->where(function ($inner) use ($field): void {
                    $inner->where('event_type', NuirRevisionEvent::TYPE_NUI_REVISION)
                        ->where('subject', $field);
                })->orWhere('event_type', NuirRevisionEvent::TYPE_DBS_REVISION);
            });
        }

        $nuiLabels = ['novelty' => 'Novelty', 'urgency' => 'Urgency', 'impact' => 'Impact'];

        $eventQuery->with('submission')->orderByDesc('recorded_at')->get()->each(function (NuirRevisionEvent $event) use ($items, $field, $nuiLabels): void {
            if ($event->event_type === NuirRevisionEvent::TYPE_DBS_REVISION) {
                $heading = 'Permintaan revisi · DBS';
            } elseif ($field === 'title') {
                $subjectLabel = $nuiLabels[$event->subject] ?? ucfirst((string) $event->subject);
                $heading = 'Revisi '.$subjectLabel.' · '.$event->actorRoleLabel();
            } else {
                $heading = 'Permintaan revisi · '.$event->actorRoleLabel();
            }

            $items->push($this->enrichHistoryItem([
                'heading' => $heading,
                'recorded_at' => $event->recorded_at,
                'actor_name' => $event->actor?->name,
                'actor_role' => $event->actor_role,
                'note' => $event->note,
                'content' => $event->submission?->{$field} ?? null,
                'kind' => 'revision_request',
                'submission_version' => $event->submission_version,
            ]));
        });

        return $items
            ->sortByDesc(fn (array $item) => $item['recorded_at']?->timestamp ?? 0)
            ->values();
    }

    public function contentFieldHasRevisionHistory(NuirSubmission $submission, string $field): bool
    {
        return $this->contentFieldHistory($submission, $field)->isNotEmpty();
    }

    /**
     * Nomor versi per elemen konten: 1 = v1, 2 = v2, dst.
     */
    public function contentFieldVersionNumber(
        NuirSubmission $submission,
        string $field,
        bool $inRevisionState = false,
    ): int {
        $history = $this->contentFieldHistory($submission, $field);
        $requests = $history->where('kind', 'revision_request')->count();

        if ($requests > 0) {
            return $requests + 1;
        }

        if ($inRevisionState || $history->contains(fn (array $item) => ($item['kind'] ?? '') === 'snapshot')) {
            return 2;
        }

        return 1;
    }

    /**
     * Label versi per elemen konten: v1, v2, v3, …
     */
    public function contentFieldVersionLabel(
        NuirSubmission $submission,
        string $field,
        bool $inRevisionState = false,
    ): string {
        return 'v'.$this->contentFieldVersionNumber($submission, $field, $inRevisionState);
    }

    /**
     * @deprecated Prefer contentFieldVersionLabel() or contentFieldVersionNumber().
     */
    public function contentFieldRevisionRound(NuirSubmission $submission, string $field): int
    {
        return $this->contentFieldVersionNumber($submission, $field);
    }

    public function contentFieldRevisionNumber(NuirSubmission $submission, string $field): int
    {
        return $this->contentFieldVersionNumber($submission, $field);
    }

    /**
     * @return Collection<int, array{heading: string, recorded_at: \Illuminate\Support\Carbon|null, actor_name: string|null, actor_role: string, note: string|null, content: string|null, kind: string, tone: string, submission_version: int|null}>
     */
    public function referenceRevisionHistory(NuirSubmission $submission, int $refOrder): Collection
    {
        $lineageIds = $this->versionLineageIds($submission);

        $events = NuirRevisionEvent::query()
            ->with('actor')
            ->whereIn('nuir_submission_id', $lineageIds)
            ->where('event_type', NuirRevisionEvent::TYPE_REFERENCE_REVISION)
            ->where('ref_order', $refOrder)
            ->orderByDesc('recorded_at')
            ->get();

        $refs = NuirReference::query()
            ->whereIn('nuir_submission_id', $events->pluck('nuir_submission_id')->unique()->values())
            ->where('ref_order', $refOrder)
            ->get()
            ->keyBy('nuir_submission_id');

        return $events
            ->map(fn (NuirRevisionEvent $event): array => $this->enrichHistoryItem([
                'heading' => 'Permintaan revisi · '.$event->actorRoleLabel(),
                'recorded_at' => $event->recorded_at,
                'actor_name' => $event->actor?->name,
                'actor_role' => $event->actor_role,
                'note' => $event->note,
                'revision_fields' => NuirReferenceRevisionFields::normalize($event->revision_fields),
                'revision_field_labels' => NuirReferenceRevisionFields::labels($event->revision_fields),
                'content' => $this->formatReferenceContent($refs->get($event->nuir_submission_id)),
                'kind' => 'revision_request',
                'submission_version' => $event->submission_version,
            ]))
            ->values();
    }

    private function formatReferenceContent(?NuirReference $ref): ?string
    {
        if (! $ref) {
            return null;
        }

        $lines = [];
        $labels = [
            'link_ojs'     => 'Link OJS',
            'indexer_name' => 'Nama Indexer',
            'link_index'   => 'Link Index',
            'link_drive'   => 'Link Drive',
            'quote'        => 'Kutipan',
            'relevance'    => 'Relevansi',
        ];

        foreach ($labels as $key => $label) {
            if (filled($ref->{$key})) {
                $lines[] = $label.': '.$ref->{$key};
            }
        }

        return $lines ? implode("\n", $lines) : null;
    }

    public function referenceHasRevisionHistory(NuirSubmission $submission, int $refOrder): bool
    {
        return $this->referenceRevisionHistory($submission, $refOrder)->isNotEmpty();
    }

    public function referenceRevisionRound(NuirSubmission $submission, int $refOrder): int
    {
        return max(1, 1 + $this->referenceRevisionHistory($submission, $refOrder)->count());
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function enrichHistoryItem(array $item): array
    {
        $item['tone'] = $this->historyTone(
            (string) ($item['kind'] ?? 'revision_request'),
            (string) ($item['actor_role'] ?? ''),
        );

        return $item;
    }

    private function historyTone(string $kind, string $actorRole): string
    {
        if ($kind === 'snapshot') {
            return 'info';
        }

        return match ($actorRole) {
            NuirRevisionEvent::ROLE_DBS => 'primary',
            NuirRevisionEvent::ROLE_VALIDATOR => 'warning',
            NuirRevisionEvent::ROLE_GUIDE1 => 'success',
            NuirRevisionEvent::ROLE_GUIDE2 => 'danger',
            'mahasiswa' => 'gray',
            default => 'gray',
        };
    }

    private function record(
        NuirSubmission $submission,
        User $actor,
        string $role,
        string $eventType,
        string $subject,
        string $note,
        ?int $refOrder = null,
        ?int $proposalId = null,
        ?array $revisionFields = null,
    ): NuirRevisionEvent {
        return NuirRevisionEvent::create([
            'nuir_submission_id' => $submission->id,
            'submission_version' => $submission->version,
            'actor_id' => $actor->id,
            'actor_role' => $role,
            'event_type' => $eventType,
            'subject' => $subject,
            'ref_order' => $refOrder,
            'nuir_proposal_id' => $proposalId,
            'note' => $note,
            'revision_fields' => NuirReferenceRevisionFields::normalize($revisionFields) ?: null,
            'recorded_at' => now(),
        ]);
    }
}
