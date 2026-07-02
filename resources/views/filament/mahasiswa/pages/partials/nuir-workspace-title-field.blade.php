@php
    use App\Support\NuirMahasiswaFieldStatus;

    $status = $this->submission
        ? NuirMahasiswaFieldStatus::titleFieldStatus($this->submission, $proposal)
        : ['label' => '—', 'color' => 'gray'];
    $showWorkflowBadge = $this->submission && NuirMahasiswaFieldStatus::isWorkflowBadge($status);
    $lastModified = $this->submission
        ? NuirMahasiswaFieldStatus::nuiFieldLastModified($this->submission, 'title')
        : null;
    $history = $this->fieldHistory('title');
    $fieldUi = $this->workspaceFieldUi('title', 'Judul');
    $fieldVersionLabel = $this->submission && filled($this->submission->title)
        ? ($fieldUi['versionLabel'] ?? NuirMahasiswaFieldStatus::resolveFieldVersionLabel($this->submission, 'title', $status))
        : null;
    $titleReadonly = $fieldUi['readonly'] && ! $this->titleEditing;
    $showTitleSave = in_array($fieldUi['action'], ['compose', 'revision'], true)
        || ($fieldUi['action'] === 'edit' && $this->titleEditing && $fieldUi['canPersist']);
    $showTitleEdit = $fieldUi['action'] === 'edit' && $fieldUi['showEdit'] && ! $this->titleEditing;
@endphp

<div
    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-950/40"
    wire:key="nui-card-title-{{ $this->submission?->id ?? 'new' }}-{{ $fieldUi['action'] }}-{{ (int) $this->titleEditing }}-{{ $this->nuiSavedStamp('title') }}"
    x-data="nuirFieldWordCounter(@js([
        'field' => 'title',
        'label' => 'Judul',
        'limit' => $this->getFieldWordLimits()['title'] ?? [],
        'lastSaved' => $lastModified ?? '',
        'uiAction' => $fieldUi['action'],
        'canPersist' => $fieldUi['canPersist'],
        'showEdit' => $fieldUi['showEdit'],
        'saveLabel' => $fieldUi['saveLabel'],
        'editLabel' => $fieldUi['editLabel'],
        'initialValue' => $this->titleField,
        'statusLabel' => $showWorkflowBadge ? $status['label'] : null,
        'statusColor' => $showWorkflowBadge ? $status['color'] : 'gray',
        'showWorkflowBadge' => $showWorkflowBadge,
        'versionLabel' => $fieldVersionLabel,
        'accordionOpen' => true,
        'keepExpandedOnSave' => true,
    ]))"
    @nuir-field-saved.window="
        if ($event.detail.field === field) {
            lastSaved = $event.detail.label || 'Diperbarui barusan';
            uiAction = $event.detail.uiAction ?? 'edit';
            canPersist = $event.detail.canPersist ?? canPersist;
            showEdit = $event.detail.showEdit ?? showEdit;
            saveLabel = $event.detail.saveLabel ?? saveLabel;
            editLabel = $event.detail.editLabel ?? editLabel;
            if ($event.detail.versionLabel) {
                versionLabel = $event.detail.versionLabel;
            }
            if ($event.detail.showWorkflowBadge) {
                showWorkflowBadge = true;
                statusLabel = $event.detail.statusLabel ?? statusLabel;
                statusColor = $event.detail.statusColor ?? statusColor;
            }
            isEditing = false;
            if ($event.detail.value != null) {
                initialValue = $event.detail.value;
            }
            ensureInputValue();
            syncTextareaLock();
            $nextTick(() => autoResize({ target: $refs.input }));
        }
    "
    @nuir-field-save-failed.window="
        if ($event.detail.field === field) {
            isEditing = true;
            syncTextareaLock();
        }
    "
    x-effect="syncTextareaLock()"
>
    <div class="space-y-3 px-4 py-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Judul
                <span
                    class="font-normal text-gray-500 dark:text-gray-400"
                    x-show="versionLabel"
                    x-cloak
                    x-text="versionLabel ? '(' + versionLabel + ')' : ''"
                ></span>
            </h3>
            <div class="flex flex-wrap items-center gap-2">
                <span
                    x-show="showWorkflowBadge && statusLabel"
                    x-cloak
                    x-bind:class="{
                        'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset': true,
                        'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30': statusColor === 'info',
                        'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30': statusColor === 'success',
                        'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30': statusColor === 'danger',
                        'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20': statusColor === 'gray',
                    }"
                    x-text="statusLabel"
                ></span>
                <span
                    class="text-xs text-gray-500 dark:text-gray-400"
                    x-show="lastSaved"
                    x-cloak
                    x-text="lastSaved"
                ></span>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="wordCountLimitText()"></span>
                <x-filament::badge color="success" size="sm" x-show="isFieldValid()" x-cloak>
                    <span x-text="wordCountInputText()"></span>
                </x-filament::badge>
                <x-filament::badge color="danger" size="sm" x-show="! isFieldValid()" x-cloak>
                    <span x-text="wordCountInputText()"></span>
                </x-filament::badge>
            </div>

            <textarea
                wire:model="titleField"
                x-ref="input"
                rows="1"
                data-nui-autoresize
                wire:key="nui-textarea-title-{{ $this->submission?->id ?? 'new' }}-{{ (int) $this->titleEditing }}"
                @input="onFieldInput(); autoResize($event)"
                @disabled($titleReadonly)
                class="nui-autoresize-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:cursor-default disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:disabled:bg-gray-900/60"
            ></textarea>

            @if ($fieldUi['action'] !== 'none')
                <div class="flex flex-wrap gap-2">
                    @if ($showTitleSave)
                        <x-filament::button
                            type="button"
                            size="sm"
                            icon="heroicon-m-check"
                            wire:click="saveTitleField"
                        >
                            {{ $fieldUi['saveLabel'] }}
                        </x-filament::button>
                    @endif

                    @if ($showTitleEdit)
                        <x-filament::button
                            type="button"
                            size="sm"
                            color="gray"
                            icon="heroicon-m-pencil-square"
                            wire:click="beginTitleEdit"
                        >
                            {{ $fieldUi['editLabel'] }}
                        </x-filament::button>
                    @endif
                </div>
            @endif
        </div>

        @include('filament.mahasiswa.pages.partials.revision-accordion', [
            'history' => $history->all(),
        ])
    </div>
</div>
