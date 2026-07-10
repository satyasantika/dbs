<?php

namespace App\Support;

use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSubmission;
use App\Services\NuirRevisionHistoryService;

class NuirMahasiswaFieldStatus
{
    public const KEY_EMPTY = 'empty';

    public const KEY_STORED = 'stored';

    public const KEY_WAITING_RESPONSE = 'waiting_response';

    public const KEY_APPROVED = 'approved';

    public const KEY_REVISION_REQUESTED = 'revision_requested';

    /** @var list<string> */
    private const STORAGE_ONLY_KEYS = [
        self::KEY_EMPTY,
    ];

    /**
     * @param  array{key: string, label: string, color: string, versionLabel?: string|null}  $status
     */
    public static function isWorkflowBadge(array $status): bool
    {
        return ! in_array($status['key'] ?? '', self::STORAGE_ONLY_KEYS, true);
    }

    public static function formatLastModified(?\Illuminate\Support\Carbon $at): ?string
    {
        return $at !== null
            ? 'Diperbarui '.$at->locale('id')->diffForHumans()
            : null;
    }

    public static function nuiFieldLastModified(NuirSubmission $submission, string $field): ?string
    {
        $savedAtColumn = $field.'_saved_at';
        $at = $submission->{$savedAtColumn} ?? null;

        if ($at === null && filled($submission->{$field})) {
            $at = $submission->updated_at;
        }

        return self::formatLastModified($at);
    }

    public static function referenceLastModified(?NuirReference $reference): ?string
    {
        if ($reference === null || ! self::referenceHasContent($reference)) {
            return null;
        }

        return self::formatLastModified($reference->updated_at);
    }

    /**
     * Returns per-guide approval status for a single NUI field.
     * Returns an empty array if field is not a reviewable NUI field or no proposal exists.
     *
     * @return list<array{label: string, color: string}>
     */
    /**
     * Returns per-guide approval status for a single NUI field.
     * Returns an empty array if field is not a reviewable NUI field or no proposal exists.
     *
     * @return list<array{label: string, color: string}>
     */
    /**
     * Per-guide approval status for a single NUI field (novelty/urgency/impact).
     *
     * @return list<array{label: string, color: string}>
     */
    public static function perGuideFieldStatuses(
        NuirSubmission $submission,
        ?NuirProposal $proposal,
        string $field,
    ): array {
        if (! $proposal || ! in_array($field, NuirContentReview::FIELDS, true)) {
            return [];
        }

        $reviews = $submission->contentReviews
            ->where('field', $field)
            ->whereIn('role', [NuirContentReview::ROLE_GUIDE1, NuirContentReview::ROLE_GUIDE2]);

        $guides = array_filter([
            NuirContentReview::ROLE_GUIDE1 => $proposal->guide1_id ? 'P1' : null,
            NuirContentReview::ROLE_GUIDE2 => $proposal->guide2_id ? 'P2' : null,
        ]);

        $statuses = [];

        foreach ($guides as $role => $guideLabel) {
            $review = $reviews->where('role', $role)->last();

            if (! $review) {
                $statuses[] = ['label' => "{$guideLabel}: Menunggu", 'color' => 'gray'];
            } elseif ($review->approved === true) {
                $statuses[] = ['label' => "{$guideLabel}: Disetujui", 'color' => 'success'];
            } else {
                $statuses[] = ['label' => "{$guideLabel}: Diminta Revisi", 'color' => 'warning'];
            }
        }

        return $statuses;
    }

    /**
     * Per-guide status for the title card, derived from NUI content reviews:
     * a guide "approves" the title if they have approved all NUI fields.
     *
     * @return list<array{label: string, color: string}>
     */
    public static function perGuideTitleStatuses(
        NuirSubmission $submission,
        ?NuirProposal $proposal,
    ): array {
        if (! $proposal) {
            return [];
        }

        $guides = array_filter([
            NuirContentReview::ROLE_GUIDE1 => $proposal->guide1_id ? 'P1' : null,
            NuirContentReview::ROLE_GUIDE2 => $proposal->guide2_id ? 'P2' : null,
        ]);

        if (empty($guides)) {
            return [];
        }

        $reviews = $submission->contentReviews
            ->whereIn('role', array_keys($guides));

        $statuses = [];

        foreach ($guides as $role => $guideLabel) {
            $guideReviews = $reviews->where('role', $role);

            if ($guideReviews->isEmpty()) {
                $statuses[] = ['label' => "{$guideLabel}: Menunggu", 'color' => 'gray'];
                continue;
            }

            if ($guideReviews->contains(fn ($r) => $r->approved === false)) {
                $statuses[] = ['label' => "{$guideLabel}: Diminta Revisi", 'color' => 'warning'];
                continue;
            }

            $approvedFields = $guideReviews->where('approved', true)->pluck('field')->unique();
            $allApproved = collect(NuirContentReview::FIELDS)
                ->every(fn ($f) => $approvedFields->contains($f));

            $statuses[] = $allApproved
                ? ['label' => "{$guideLabel}: Disetujui", 'color' => 'success']
                : ['label' => "{$guideLabel}: Menunggu", 'color' => 'gray'];
        }

        return $statuses;
    }

    /**
     * @return array{key: string, label: string, color: string, versionLabel?: string|null}
     */
    public static function nuiFieldStatus(NuirSubmission $submission, ?NuirProposal $proposal, string $field): array
    {
        if (! in_array($field, NuirContentReview::FIELDS, true)) {
            return self::statusEmpty();
        }

        $elementLabel = self::fieldElementLabel($field);

        if (! $proposal) {
            if (! filled($submission->{$field})) {
                return self::statusEmpty();
            }

            $status = in_array($submission->status, ['submitted', 'content_ok', 'revision'], true)
                ? self::statusWaitingResponse($elementLabel)
                : self::statusStored($elementLabel);

            return self::finalizeFieldStatus($submission, $field, $status);
        }

        $reviews = $submission->contentReviews
            ->where('field', $field)
            ->whereIn('role', [NuirContentReview::ROLE_GUIDE1, NuirContentReview::ROLE_GUIDE2]);

        if ($reviews->contains(fn ($review) => $review->approved === false)) {
            return self::finalizeFieldStatus($submission, $field, self::statusRevisionRequested($elementLabel));
        }

        $expected = collect([
            $proposal->guide1_id ? NuirContentReview::ROLE_GUIDE1 : null,
            $proposal->guide2_id ? NuirContentReview::ROLE_GUIDE2 : null,
        ])->filter()->values();

        if ($expected->isEmpty()) {
            return self::finalizeFieldStatus($submission, $field, self::statusWaitingResponse($elementLabel));
        }

        $approvedRoles = $reviews
            ->where('approved', true)
            ->pluck('role')
            ->unique();

        if ($approvedRoles->count() >= $expected->count()) {
            return self::finalizeFieldStatus($submission, $field, self::statusApproved($elementLabel));
        }

        return self::finalizeFieldStatus($submission, $field, self::statusWaitingResponse($elementLabel));
    }

    /**
     * @return array{key: string, label: string, color: string, versionLabel?: string|null}
     */
    public static function titleFieldStatus(NuirSubmission $submission, ?NuirProposal $proposal = null): array
    {
        if (! filled($submission->title)) {
            return self::statusEmpty();
        }

        if ($submission->status === 'revision') {
            return self::finalizeFieldStatus($submission, 'title', self::statusRevisionRequested('Judul'));
        }

        if (in_array($submission->status, ['content_ok', 'finalized'], true)) {
            return self::finalizeFieldStatus($submission, 'title', self::statusApproved('Judul'));
        }

        // Title approved when all expected guides have approved all NUI fields (no DBS step needed)
        if ($proposal !== null && self::allGuidesApproveAllNui($submission, $proposal)) {
            return self::finalizeFieldStatus($submission, 'title', self::statusApproved('Judul'));
        }

        if ($proposal !== null || $submission->status === 'submitted') {
            return self::finalizeFieldStatus($submission, 'title', self::statusWaitingResponse('Judul'));
        }

        return self::finalizeFieldStatus($submission, 'title', self::statusStored('Judul'));
    }

    public static function allGuidesApproveAllNui(NuirSubmission $submission, NuirProposal $proposal): bool
    {
        $roles = array_filter([
            $proposal->guide1_id ? NuirContentReview::ROLE_GUIDE1 : null,
            $proposal->guide2_id ? NuirContentReview::ROLE_GUIDE2 : null,
        ]);

        if (empty($roles)) {
            return false;
        }

        foreach ($roles as $role) {
            foreach (NuirContentReview::FIELDS as $field) {
                $approved = $submission->contentReviews
                    ->where('role', $role)
                    ->where('field', $field)
                    ->where('approved', true)
                    ->isNotEmpty();

                if (! $approved) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array{key: string, label: string, color: string, versionLabel?: string|null}
     */
    public static function referenceStatus(?NuirReference $reference): array
    {
        if ($reference === null || ! self::referenceHasContent($reference)) {
            return ['key' => self::KEY_EMPTY, 'label' => 'R: Belum diisi', 'color' => 'gray'];
        }

        return match ($reference->ref_approved) {
            true => ['key' => self::KEY_APPROVED, 'label' => 'Disetujui Validator', 'color' => 'success'],
            false => ['key' => self::KEY_REVISION_REQUESTED, 'label' => 'Diminta Revisi Validator', 'color' => 'danger'],
            default => ['key' => self::KEY_WAITING_RESPONSE, 'label' => 'Menunggu Respon Validator', 'color' => 'info'],
        };
    }

    public static function referenceHasContent(NuirReference $reference): bool
    {
        return collect([
            $reference->link_ojs,
            $reference->indexer_name,
            $reference->link_index,
            $reference->link_drive,
            $reference->quote,
            $reference->relevance,
        ])->contains(fn ($value) => filled($value));
    }

    /**
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    public static function workspaceFieldUi(
        ?NuirSubmission $submission,
        ?NuirProposal $proposal,
        string $field,
        string $fieldLabel,
        bool $canCreateWithoutSubmission = false,
    ): array {
        if ($submission === null) {
            if (! $canCreateWithoutSubmission) {
                return self::uiNone($fieldLabel);
            }

            return self::uiCompose($fieldLabel);
        }

        $isSaved = filled($submission->{$field}) || $submission->{"{$field}_saved_at"} !== null;
        $status = $field === 'title'
            ? self::titleFieldStatus($submission, $proposal)
            : self::nuiFieldStatus($submission, $proposal, $field);
        $versionLabel = $status['versionLabel']
            ?? self::resolveFieldVersionLabel($submission, $field, $status);

        if ($field === 'title') {
            return self::titleWorkspaceUi($submission, $proposal, $fieldLabel, $isSaved, $versionLabel);
        }

        return self::nuiContentWorkspaceUi($submission, $field, $fieldLabel, $isSaved, $status, $versionLabel);
    }

    /**
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    private static function titleWorkspaceUi(
        NuirSubmission $submission,
        ?NuirProposal $proposal,
        string $fieldLabel,
        bool $isSaved,
        ?string $versionLabel,
    ): array {
        if ($submission->status === 'revision') {
            return $isSaved ? self::uiRevision($fieldLabel, $versionLabel) : self::uiCompose($fieldLabel, $versionLabel);
        }

        if (! $isSaved) {
            return self::uiCompose($fieldLabel, $versionLabel);
        }

        // Both candidate guides already approved (DBS-level content_ok/finalized
        // is a separate gate, not covered here) — student can still propose a
        // revision, which resets both guides' approval on this field.
        if (! in_array($submission->status, ['content_ok', 'finalized'], true)
            && $proposal !== null
            && self::allGuidesApproveAllNui($submission, $proposal)
        ) {
            return self::uiApprovedRevisable($fieldLabel, $versionLabel);
        }

        $canPersist = $submission->isNuiFieldEditable('title');

        return self::uiEdit(
            $fieldLabel,
            canPersist: $canPersist,
            showEdit: $canPersist,
            versionLabel: $versionLabel,
        );
    }

    /**
     * @param  array{key: string, label: string, color: string, versionLabel?: string|null}  $status
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    private static function nuiContentWorkspaceUi(
        NuirSubmission $submission,
        string $field,
        string $fieldLabel,
        bool $isSaved,
        array $status,
        ?string $versionLabel,
    ): array {
        if ($status['key'] === self::KEY_APPROVED) {
            return self::uiApprovedRevisable($fieldLabel, $versionLabel);
        }

        if ($status['key'] === self::KEY_REVISION_REQUESTED) {
            return $isSaved ? self::uiRevision($fieldLabel, $versionLabel) : self::uiCompose($fieldLabel, $versionLabel);
        }

        if (! $isSaved) {
            return self::uiCompose($fieldLabel, $versionLabel);
        }

        $canPersist = $submission->isNuiFieldEditable($field);

        return self::uiEdit(
            $fieldLabel,
            canPersist: $canPersist,
            showEdit: $canPersist,
            versionLabel: $versionLabel,
        );
    }

    /**
     * @param  array{key: string, label: string, color: string}  $status
     * @return array{key: string, label: string, color: string, versionLabel?: string|null}
     */
    private static function finalizeFieldStatus(NuirSubmission $submission, string $field, array $status): array
    {
        if (in_array($status['key'], self::STORAGE_ONLY_KEYS, true)) {
            return $status;
        }

        $versionLabel = self::resolveFieldVersionLabel($submission, $field, $status);
        $elementLabel = self::fieldElementLabel($field);

        return [
            ...$status,
            'label' => self::contextualLabel($elementLabel, self::statusMessageFromKey($status['key']), $versionLabel),
            'versionLabel' => $versionLabel,
        ];
    }

    public static function resolveFieldVersionLabel(NuirSubmission $submission, string $field, array $status): ?string
    {
        if (($status['key'] ?? '') === self::KEY_EMPTY) {
            return null;
        }

        $inRevisionState = ($status['key'] ?? '') === self::KEY_REVISION_REQUESTED
            || ($field === 'title' && $submission->status === 'revision');

        return app(NuirRevisionHistoryService::class)
            ->contentFieldVersionLabel($submission, $field, $inRevisionState);
    }

    private static function contextualLabel(string $elementLabel, string $statusMessage, string $versionLabel): string
    {
        return "{$elementLabel} ({$versionLabel}): ".mb_strtolower($statusMessage);
    }

    private static function statusMessageFromKey(string $key): string
    {
        return match ($key) {
            self::KEY_WAITING_RESPONSE => 'Menunggu Respon',
            self::KEY_APPROVED => 'Disetujui',
            self::KEY_REVISION_REQUESTED => 'Diminta Revisi',
            self::KEY_STORED => 'Tersimpan',
            default => '',
        };
    }

    private static function fieldElementLabel(string $field): string
    {
        return match ($field) {
            'title' => 'Judul',
            'novelty' => 'Novelty',
            'urgency' => 'Urgency',
            'impact' => 'Impact',
            default => ucfirst($field),
        };
    }

    private static function versionSuffix(?string $versionLabel): string
    {
        return filled($versionLabel) ? " ({$versionLabel})" : '';
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private static function statusEmpty(): array
    {
        return [
            'key' => self::KEY_EMPTY,
            'label' => 'Belum diisi',
            'color' => 'gray',
        ];
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private static function statusStored(string $elementLabel): array
    {
        return [
            'key' => self::KEY_STORED,
            'label' => "{$elementLabel}: Tersimpan",
            'color' => 'gray',
        ];
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private static function statusWaitingResponse(string $elementLabel): array
    {
        return [
            'key' => self::KEY_WAITING_RESPONSE,
            'label' => "{$elementLabel}: Menunggu Respon",
            'color' => 'info',
        ];
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private static function statusApproved(string $elementLabel): array
    {
        return [
            'key' => self::KEY_APPROVED,
            'label' => "{$elementLabel}: Disetujui",
            'color' => 'success',
        ];
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private static function statusRevisionRequested(string $elementLabel): array
    {
        return [
            'key' => self::KEY_REVISION_REQUESTED,
            'label' => "{$elementLabel}: Diminta Revisi",
            'color' => 'danger',
        ];
    }

    /**
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    private static function uiCompose(string $fieldLabel, ?string $versionLabel = null): array
    {
        $suffix = self::versionSuffix($versionLabel);

        return [
            'action' => 'compose',
            'readonly' => false,
            'canPersist' => true,
            'showEdit' => false,
            'saveLabel' => "Simpan {$fieldLabel}{$suffix}",
            'editLabel' => "Edit {$fieldLabel}{$suffix}",
            'versionLabel' => $versionLabel,
        ];
    }

    /**
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    private static function uiEdit(
        string $fieldLabel,
        bool $canPersist = true,
        bool $showEdit = true,
        ?string $versionLabel = null,
    ): array {
        $suffix = self::versionSuffix($versionLabel);

        return [
            'action' => 'edit',
            'readonly' => true,
            'canPersist' => $canPersist,
            'showEdit' => $showEdit,
            'saveLabel' => "Simpan {$fieldLabel}{$suffix}",
            'editLabel' => "Edit {$fieldLabel}{$suffix}",
            'versionLabel' => $versionLabel,
        ];
    }

    /**
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    private static function uiRevision(string $fieldLabel, ?string $versionLabel = null): array
    {
        $suffix = self::versionSuffix($versionLabel);

        return [
            'action' => 'revision',
            'readonly' => false,
            'canPersist' => true,
            'showEdit' => false,
            'saveLabel' => "Simpan Revisi {$fieldLabel}{$suffix}",
            'editLabel' => "Buat Revisi {$fieldLabel}{$suffix}",
            'versionLabel' => $versionLabel,
        ];
    }

    /**
     * Field sudah disetujui kedua calon pembimbing tapi mahasiswa tetap bisa
     * menekan Edit untuk mengajukan revisi — labelnya beda dari uiEdit() biasa
     * supaya jelas bahwa menyimpan di sini akan membuka lagi status persetujuan.
     *
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    private static function uiApprovedRevisable(string $fieldLabel, ?string $versionLabel = null): array
    {
        $suffix = self::versionSuffix($versionLabel);

        return [
            'action' => 'edit',
            'readonly' => true,
            'canPersist' => true,
            'showEdit' => true,
            'saveLabel' => "Ajukan Revisi {$fieldLabel}{$suffix}",
            'editLabel' => "Edit {$fieldLabel}{$suffix}",
            'versionLabel' => $versionLabel,
        ];
    }

    /**
     * @return array{
     *     action: 'compose'|'edit'|'revision'|'none',
     *     readonly: bool,
     *     canPersist: bool,
     *     showEdit: bool,
     *     saveLabel: string,
     *     editLabel: string,
     *     versionLabel?: string|null,
     * }
     */
    private static function uiNone(string $fieldLabel, ?string $versionLabel = null): array
    {
        return [
            'action' => 'none',
            'readonly' => true,
            'canPersist' => false,
            'showEdit' => false,
            'saveLabel' => '',
            'editLabel' => '',
            'versionLabel' => $versionLabel,
        ];
    }
}
