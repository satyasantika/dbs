<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Filament\Mahasiswa\Concerns\HidesNuirNavigationWhenInactive;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirMahasiswaWorkspaceService;
use App\Support\NuirMahasiswaFieldStatus;
use App\Support\NuirTextLimits;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

class NuirSubmissionOverview extends Page
{
    use AuthorizesMahasiswaPanelAccess;
    use HidesNuirNavigationWhenInactive;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pengajuan NUIR';

    protected static ?string $title = 'Pengajuan NUIR';

    protected static ?string $navigationGroup = 'NUIR';

    protected static ?string $slug = 'nuir-submission';

    protected static string $view = 'filament.mahasiswa.pages.nuir-submission-workspace';

    public ?NuirSetting $setting = null;

    public ?NuirSubmission $submission = null;

    public bool $closed = false;

    public bool $stage3 = false;

    public bool $nuiComplete = false;

    public bool $nuiFieldsFilled = false;

    public bool $titleSaved = false;

    public bool $titleEditing = false;

    public ?string $editingField = null;

    public string $titleField = '';

    public string $noveltyField = '';

    public string $urgencyField = '';

    public string $impactField = '';

    /** @var array<int, array<string, mixed>> */
    public array $referenceFields = [];

    public ?int $guide1Selection = null;

    public ?int $guide2Selection = null;

    public function mount(NuirMahasiswaWorkspaceService $workspace): void
    {
        $this->refreshWorkspace($workspace);
    }

    public function createSubmission(NuirMahasiswaWorkspaceService $workspace): void
    {
        try {
            $this->submission = $workspace->createSubmission(auth()->user());
            $this->refreshWorkspace($workspace);
            Notification::make()->success()->title('Pengajuan NUIR baru dibuat.')->send();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
        }
    }

    public function saveNuiField(string $field, string $value, NuirMahasiswaWorkspaceService $workspace): void
    {
        if (! in_array($field, ['title', 'novelty', 'urgency', 'impact'], true)) {
            $this->notifySaveFailed(ucfirst($field), 'Field tidak dikenali.');

            return;
        }

        if (! $this->ensureSubmission($workspace)) {
            return;
        }

        match ($field) {
            'title' => $this->titleField = $value,
            'novelty' => $this->noveltyField = $value,
            'urgency' => $this->urgencyField = $value,
            'impact' => $this->impactField = $value,
            default => null,
        };

        try {
            $workspace->saveNuiField($this->submission, auth()->user(), $field, $value);
            $this->submission->refresh();
            $this->refreshWorkspace($workspace);
            $ui = $this->workspaceFieldUi($field, $this->nuiFieldLabel($field));
            $savedLabel = NuirMahasiswaFieldStatus::nuiFieldLastModified($this->submission, $field)
                ?: 'Diperbarui barusan';
            $proposal = $this->submission ? $this->activeProposal : null;
            $fieldStatus = $field === 'title'
                ? NuirMahasiswaFieldStatus::titleFieldStatus($this->submission, $proposal)
                : NuirMahasiswaFieldStatus::nuiFieldStatus($this->submission, $proposal, $field);
            $this->dispatch(
                'nuir-field-saved',
                field: $field,
                label: $savedLabel,
                value: $value,
                uiAction: $ui['action'],
                canPersist: $ui['canPersist'],
                showEdit: $ui['showEdit'],
                saveLabel: $ui['saveLabel'],
                editLabel: $ui['editLabel'],
                statusLabel: $fieldStatus['label'],
                statusColor: $fieldStatus['color'],
                showWorkflowBadge: NuirMahasiswaFieldStatus::isWorkflowBadge($fieldStatus),
                versionLabel: $fieldStatus['versionLabel'] ?? $ui['versionLabel'] ?? null,
                nuiComplete: $this->nuiComplete,
                nuiFieldsFilled: $this->nuiFieldsFilled,
                titleSaved: $this->titleSaved,
            );

            if ($this->nuiFieldsFilled) {
                $this->dispatch('nuir-nui-complete');
            }
            $this->notifySuccess(
                $this->nuiFieldLabel($field).' berhasil disimpan.',
                $this->nuiFieldSaveSuccessBody($field, $value),
            );
        } catch (ValidationException $exception) {
            $reason = collect($exception->errors())->flatten()->first();

            $this->dispatch('nuir-field-save-failed', field: $field);
            $this->notifySaveFailed($this->nuiFieldLabel($field), $reason);
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch('nuir-field-save-failed', field: $field);
            $this->notifySaveFailed(
                $this->nuiFieldLabel($field),
                'Terjadi kesalahan saat menyimpan. Pastikan migrasi database terbaru sudah dijalankan.',
            );
        }
    }

    public function saveTitleField(NuirMahasiswaWorkspaceService $workspace): void
    {
        $this->titleField = trim($this->titleField);

        if ($this->titleField === '') {
            $this->notifySaveFailed('Judul', 'Judul wajib diisi.');

            return;
        }

        $this->saveNuiField('title', $this->titleField, $workspace);
        $this->titleEditing = false;
    }

    public function beginTitleEdit(): void
    {
        if ($this->isWorkspaceFieldEditable('title')) {
            $this->titleEditing = true;
        }
    }

    public function cancelTitleEdit(): void
    {
        $this->titleEditing = false;
        $this->titleField = (string) ($this->submission?->title ?? '');
    }

    public function beginFieldEdit(string $field): void
    {
        if (in_array($field, ['novelty', 'urgency', 'impact'], true)) {
            $this->editingField = $field;
        }
    }

    public function cancelFieldEdit(): void
    {
        $field = $this->editingField;
        $this->editingField = null;

        if ($field && $this->submission) {
            $property = $field.'Field';
            $this->{$property} = (string) ($this->submission->{$field} ?? '');
        }
    }

    public function saveNoveltyField(NuirMahasiswaWorkspaceService $workspace): void
    {
        $this->saveNuiField('novelty', $this->noveltyField, $workspace);
    }

    public function saveUrgencyField(NuirMahasiswaWorkspaceService $workspace): void
    {
        $this->saveNuiField('urgency', $this->urgencyField, $workspace);
    }

    public function saveImpactField(NuirMahasiswaWorkspaceService $workspace): void
    {
        $this->saveNuiField('impact', $this->impactField, $workspace);
    }

    public function notifySaveFailed(string $label, string $reason): void
    {
        Notification::make()
            ->danger()
            ->title($label.' gagal disimpan')
            ->body('Tidak sesuai aturan: '.$reason)
            ->duration(8000)
            ->send();
    }

    public function notifySuccess(string $title, ?string $body = null): void
    {
        $notification = Notification::make()
            ->success()
            ->title($title)
            ->duration(5000);

        if ($body !== null) {
            $notification->body($body);
        }

        $notification->send();
    }

    public function notifyDanger(string $message, ?string $body = null): void
    {
        $notification = Notification::make()
            ->danger()
            ->title($message)
            ->duration(8000);

        if ($body !== null) {
            $notification->body($body);
        }

        $notification->send();
    }

    public function saveReference(int $order, NuirMahasiswaWorkspaceService $workspace): void
    {
        if (! $this->submission) {
            return;
        }

        $fields = $this->referenceFields[$order] ?? [];

        if (! collect($fields)->contains(fn ($value) => filled($value))) {
            $this->notifyDanger('Isi minimal satu bagian referensi sebelum menyimpan.');

            return;
        }

        try {
            $workspace->saveReference(
                $this->submission,
                auth()->user(),
                $order,
                $this->referenceFields[$order] ?? [],
            );
            $this->refreshWorkspace($workspace);
            Notification::make()
                ->success()
                ->title('Referensi #'.$order.' berhasil disimpan.')
                ->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title(collect($exception->errors())->flatten()->first())
                ->send();
        } catch (\Throwable $exception) {
            report($exception);
            $this->notifyDanger('Referensi #'.$order.' gagal disimpan', 'Terjadi kesalahan saat menyimpan.');
        }
    }

    public function proposeGuide(int $seat, NuirMahasiswaWorkspaceService $workspace): void
    {
        if (! $this->submission) {
            return;
        }

        $guideId = $seat === 1 ? $this->guide1Selection : $this->guide2Selection;

        if (blank($guideId)) {
            Notification::make()->danger()->title('Pilih calon pembimbing terlebih dahulu.')->send();

            return;
        }

        try {
            $workspace->proposeGuideSeat($this->submission, auth()->user(), $seat, (int) $guideId);
            $this->refreshWorkspace($workspace);
            Notification::make()->success()->title('Usulan Pembimbing '.$seat.' dikirim.')->send();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
        } catch (\Throwable $exception) {
            report($exception);
            $this->notifyDanger('Usulan gagal dikirim', 'Terjadi kesalahan. Coba lagi beberapa saat.');
        }
    }

    #[Computed]
    public function activeProposal()
    {
        if (! $this->submission) {
            return null;
        }

        return app(NuirMahasiswaWorkspaceService::class)->activeProposal($this->submission);
    }

    #[Computed]
    public function rejectionHistory(): Collection
    {
        if (! $this->submission) {
            return collect();
        }

        return collect(app(NuirMahasiswaWorkspaceService::class)->workspaceData(auth()->user())['rejectionHistory'] ?? []);
    }

    #[Computed]
    public function referenceSlots(): array
    {
        return range(1, (int) ($this->setting?->max_references ?? 10));
    }

    #[Computed]
    public function lecturersP1()
    {
        if (! $this->submission) {
            return collect();
        }

        return app(NuirMahasiswaWorkspaceService::class)->workspaceData(auth()->user())['lecturersP1'] ?? collect();
    }

    #[Computed]
    public function lecturersP2()
    {
        if (! $this->submission) {
            return collect();
        }

        return app(NuirMahasiswaWorkspaceService::class)->workspaceData(auth()->user())['lecturersP2'] ?? collect();
    }

    public function fieldHistory(string $field): Collection
    {
        if (! $this->submission) {
            return collect();
        }

        return app(NuirMahasiswaWorkspaceService::class)->fieldHistory($this->submission, $field);
    }

    public function workspaceFieldUi(string $field, string $label): array
    {
        return NuirMahasiswaFieldStatus::workspaceFieldUi(
            $this->submission,
            $this->activeProposal,
            $field,
            $label,
            auth()->user()?->can('create nuir submission') ?? false,
        );
    }

    public function isWorkspaceFieldEditable(string $field): bool
    {
        if (! $this->submission) {
            return auth()->user()?->can('create nuir submission') ?? false;
        }

        return $this->submission->isNuiFieldEditable($field);
    }

    public function nuiSavedStamp(string $field): int
    {
        if ($this->submission === null) {
            return 0;
        }

        $column = $field === 'title' ? 'title_saved_at' : "{$field}_saved_at";

        return $this->submission->{$column}?->timestamp ?? 0;
    }

    public function nuiFieldAccordionOpen(string $field): bool
    {
        return ! app(NuirMahasiswaWorkspaceService::class)
            ->isNuiFieldFilled($this->submission, $field);
    }

    public function referenceSlotAccordionOpen(int $order): bool
    {
        if ($this->submission === null) {
            return true;
        }

        $reference = $this->submission->references->firstWhere('ref_order', $order);

        return ! app(NuirMahasiswaWorkspaceService::class)
            ->isReferenceSlotFilled($reference);
    }

    protected function ensureSubmission(NuirMahasiswaWorkspaceService $workspace): bool
    {
        if ($this->submission) {
            return true;
        }

        try {
            $this->submission = $workspace->createSubmission(auth()->user());

            return true;
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title(collect($exception->errors())->flatten()->first())
                ->send();

            return false;
        }
    }

    public function referenceHistory(int $refOrder): Collection
    {
        if (! $this->submission) {
            return collect();
        }

        return app(NuirMahasiswaWorkspaceService::class)->referenceHistory($this->submission, $refOrder);
    }

    public function wordLimitHint(string $field): string
    {
        if (! $this->setting) {
            return '';
        }

        return app(NuirMahasiswaWorkspaceService::class)->wordLimitHint($this->setting, $field);
    }

    /** @return array<string, array{min: int|null, max: int|null, maxChars: int|null}> */
    public function getFieldWordLimits(): array
    {
        if (! $this->setting) {
            return [];
        }

        $setting = $this->setting;

        return [
            'title' => [
                'min' => null,
                'max' => $setting->max_words_title,
                'maxChars' => null,
            ],
            'novelty' => [
                'min' => null,
                'max' => $setting->max_words_novelty,
                'maxChars' => $setting->max_chars_novelty,
            ],
            'urgency' => [
                'min' => null,
                'max' => $setting->max_words_urgency,
                'maxChars' => $setting->max_chars_urgency,
            ],
            'impact' => [
                'min' => null,
                'max' => $setting->max_words_impact,
                'maxChars' => $setting->max_chars_impact,
            ],
        ];
    }

    public function guideSeatState(int $seat): array
    {
        $proposal = $this->activeProposal;

        if (! $this->submission || ! $proposal) {
            return [
                'guide_id' => null,
                'status' => 'pending',
                'note' => null,
                'can_change' => true,
                'is_readonly' => false,
            ];
        }

        return app(NuirMahasiswaWorkspaceService::class)->guideSeatState($this->submission, $proposal, $seat);
    }

    protected function nuiFieldLabel(string $field): string
    {
        return match ($field) {
            'title' => 'Judul',
            'novelty' => 'Novelty',
            'urgency' => 'Urgency',
            'impact' => 'Impact',
            default => ucfirst($field),
        };
    }

    protected function nuiFieldSaveSuccessBody(string $field, string $value): string
    {
        $limits = $this->getFieldWordLimits()[$field] ?? [];
        $words = NuirTextLimits::wordCount($value);

        if (($limits['maxChars'] ?? null) !== null && ($limits['max'] ?? null) === null) {
            $chars = mb_strlen($value);

            return "{$chars} karakter diinput, sesuai aturan (maks. {$limits['maxChars']} karakter).";
        }

        $parts = [];

        if (($limits['min'] ?? null) !== null) {
            $parts[] = 'min. '.$limits['min'].' kata';
        }

        if (($limits['max'] ?? null) !== null) {
            $parts[] = 'maks. '.$limits['max'].' kata';
        }

        $limitText = $parts !== [] ? implode(', ', $parts) : 'batas yang ditetapkan';

        return "{$words} kata diinput, sesuai aturan ({$limitText}).";
    }

    protected function refreshWorkspace(NuirMahasiswaWorkspaceService $workspace): void
    {
        $data = $workspace->workspaceData(auth()->user());

        $this->setting = $data['setting'];
        $this->submission = $data['submission'];
        $this->closed = $data['closed'];
        $this->stage3 = $data['stage3'];
        $this->nuiFieldsFilled = $data['submission']
            ? $workspace->hasAllNuiFieldsFilled($data['submission'])
            : false;
        $this->titleSaved = $data['submission']?->title_saved_at !== null;
        $this->titleEditing = false;
        $this->editingField = null;
        $this->nuiComplete = $data['submission'] && $data['setting']
            ? $workspace->isNuiComplete($data['submission'], $data['setting'])
            : false;

        if ($this->submission) {
            $this->submission->load(['references', 'contentReviews', 'proposals.guide1', 'proposals.guide2']);

            $this->titleField = (string) ($this->submission->title ?? '');
            $this->noveltyField = (string) ($this->submission->novelty ?? '');
            $this->urgencyField = (string) ($this->submission->urgency ?? '');
            $this->impactField = (string) ($this->submission->impact ?? '');

            $this->referenceFields = [];

            foreach (range(1, (int) ($this->setting?->max_references ?? 10)) as $order) {
                $reference = $this->submission->references->firstWhere('ref_order', $order);

                $this->referenceFields[$order] = [
                    'link_ojs' => $reference?->link_ojs ?? '',
                    'indexer_name' => $reference?->indexer_name ?? '',
                    'link_index' => $reference?->link_index ?? '',
                    'link_drive' => $reference?->link_drive ?? '',
                    'quote' => $reference?->quote ?? '',
                    'relevance' => $reference?->relevance ?? '',
                ];
            }

            $proposal = $workspace->activeProposal($this->submission);
            $this->guide1Selection = $proposal?->guide1_id;
            $this->guide2Selection = $proposal?->guide2_id;
        }

        unset($this->activeProposal, $this->rejectionHistory, $this->referenceSlots, $this->lecturersP1, $this->lecturersP2);
    }

    protected static function mahasiswaAccessPermission(): string
    {
        return 'access nuir/submission';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
