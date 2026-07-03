<?php

namespace App\Filament\Mahasiswa\Widgets;

use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Models\NuirSubmission;
use App\Services\NuirMahasiswaWorkspaceService;
use App\Services\NuirService;
use App\Support\NuirMahasiswaFieldStatus;
use Filament\Widgets\Widget;

class MahasiswaNuirComponentStatusWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.mahasiswa.widgets.nuir-component-status-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        $setting = app(NuirService::class)->getActiveSetting($user);

        if (! $setting || ! $setting->active || ! in_array($setting->stage, [1, 2], true)) {
            return false;
        }

        return app(NuirService::class)->hasSubmission($user);
    }

    public function workspaceUrl(): string
    {
        return NuirSubmissionOverview::getUrl(panel: 'mahasiswa');
    }

    /**
     * @return list<array{
     *     label: string,
     *     field: string,
     *     status: array{key: string, label: string, color: string},
     *     perGuide: list<array{label: string, color: string}>,
     * }>
     */
    public function getComponentStatuses(): array
    {
        $user = auth()->user();
        $submission = app(NuirService::class)->activeSubmission($user);
        $proposal = null;

        if ($submission) {
            $submission->load(['contentReviews', 'proposals']);
            $proposal = app(NuirMahasiswaWorkspaceService::class)->activeProposal($submission);
        }

        $titleSaved = $submission?->title_saved_at !== null;

        return collect([
            'title' => 'Judul',
            'novelty' => 'Novelty',
            'urgency' => 'Urgency',
            'impact' => 'Impact',
        ])->map(function (string $label, string $field) use ($submission, $proposal, $titleSaved): array {
            return [
                'label' => $label,
                'field' => $field,
                'status' => $this->resolveFieldStatus($submission, $proposal, $field, $titleSaved),
                'perGuide' => $this->resolvePerGuideStatuses($submission, $proposal, $field, $titleSaved),
            ];
        })->values()->all();
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private function resolveFieldStatus(
        ?NuirSubmission $submission,
        $proposal,
        string $field,
        bool $titleSaved,
    ): array {
        if ($submission === null) {
            return $this->emptyStatus();
        }

        if ($field !== 'title' && ! $titleSaved) {
            return $this->emptyStatus();
        }

        if ($field === 'title') {
            return NuirMahasiswaFieldStatus::titleFieldStatus($submission, $proposal);
        }

        if (! filled($submission->{$field}) && $submission->{"{$field}_saved_at"} === null) {
            return $this->emptyStatus();
        }

        return NuirMahasiswaFieldStatus::nuiFieldStatus($submission, $proposal, $field);
    }

    /**
     * @return list<array{label: string, color: string}>
     */
    private function resolvePerGuideStatuses(
        ?NuirSubmission $submission,
        $proposal,
        string $field,
        bool $titleSaved,
    ): array {
        if ($submission === null || $proposal === null) {
            return [];
        }

        if ($field !== 'title' && ! $titleSaved) {
            return [];
        }

        if ($field === 'title') {
            return NuirMahasiswaFieldStatus::perGuideTitleStatuses($submission, $proposal);
        }

        if (! filled($submission->{$field}) && $submission->{"{$field}_saved_at"} === null) {
            return [];
        }

        return NuirMahasiswaFieldStatus::perGuideFieldStatuses($submission, $proposal, $field);
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private function emptyStatus(): array
    {
        return [
            'key' => NuirMahasiswaFieldStatus::KEY_EMPTY,
            'label' => 'Belum diisi',
            'color' => 'gray',
        ];
    }
}
