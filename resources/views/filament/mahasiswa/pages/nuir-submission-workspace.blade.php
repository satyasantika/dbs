@php
    use App\Models\NuirContentReview;
    use App\Support\NuirMahasiswaFieldStatus;
    use App\Support\NuirReferenceRevisionFields;
    use App\Support\NuirTextLimits;
@endphp

<x-filament-panels::page>

<div wire:poll.15s.visible="pollWorkspace"></div>

<style>
    .nuir-auto { resize: none; overflow-y: hidden; }
</style>

<div class="space-y-6">

@if ($this->stage3)
    <x-filament::section>
        <p class="text-gray-600 dark:text-gray-400">
            Angkatan Anda tidak memerlukan pengajuan NUIR. Pembimbing akan ditetapkan langsung oleh DBS.
        </p>
    </x-filament::section>

@elseif ($this->closed)
    <x-filament::section>
        <p class="text-gray-500 dark:text-gray-400">NUIR belum dibuka untuk angkatan Anda.</p>
    </x-filament::section>

@else

@php
    $proposal = $this->submission ? $this->activeProposal : null;
    $limits   = $this->getFieldWordLimits();
@endphp

{{-- ═══════════════════════════════════════════════════════
     CARD JUDUL
     ═══════════════════════════════════════════════════════ --}}
@php
    $tLimits            = $limits['title'] ?? [];
    $tMin               = $tLimits['min'] ?? null;
    $tMax               = $tLimits['max'] ?? null;
    $tHint              = $this->wordLimitHint('title');
    $tWc                = NuirTextLimits::wordCount($this->titleField);
    $tStatus            = $this->submission
        ? NuirMahasiswaFieldStatus::titleFieldStatus($this->submission, $proposal)
        : ['key' => 'empty', 'label' => '', 'color' => 'gray'];
    $tHistory           = $this->fieldHistory('title');
    $tPerGuide          = $this->submission
        ? NuirMahasiswaFieldStatus::perGuideTitleStatuses($this->submission, $proposal)
        : [];
    $tHasGuideRevision  = collect($tPerGuide)->contains('color', 'warning');
    // Per-guide revision notes for the pinned banner (same style as NUI)
    $tGuideRevNotes     = ($tHasGuideRevision && $this->submission)
        ? collect([NuirContentReview::ROLE_GUIDE1 => 'P1', NuirContentReview::ROLE_GUIDE2 => 'P2'])
            ->map(fn ($label, $role) => [
                'label' => $label,
                'note'  => $this->submission->contentReviews
                    ->where('approved', false)
                    ->where('role', $role)
                    ->last()?->note,
            ])
            ->filter(fn ($r) => filled($r['note']))
            ->values()
        : collect();
    // DBS revision note (fallback when no guide revision)
    $tLatestRevNote     = (! $tGuideRevNotes->isNotEmpty() && $tStatus['key'] === 'revision_requested')
        ? $tHistory->firstWhere('kind', 'revision_request')
        : null;
    $tUi                = $this->workspaceFieldUi('title', 'Judul');
    $tRo                = $tUi['readonly'] && ! $this->titleEditing;
    $tSave              = in_array($tUi['action'], ['compose', 'revision'], true)
                          || ($tUi['action'] === 'edit' && $this->titleEditing);
    $tEdit              = $tUi['action'] === 'edit' && $tUi['showEdit'] && ! $this->titleEditing && ! $tHasGuideRevision;
    $tCancel            = $tUi['action'] === 'edit' && $this->titleEditing;
    // When guides have requested revision, force editable + save button (bypass UI edit-mode toggle)
    if ($tHasGuideRevision) {
        $tRo   = false;
        $tSave = true;
        $tEdit = false;
    }
    $tBadge             = $this->submission && NuirMahasiswaFieldStatus::isWorkflowBadge($tStatus);
    $tLast              = $this->submission
        ? NuirMahasiswaFieldStatus::nuiFieldLastModified($this->submission, 'title')
        : null;
    $tAccordionColor = $this->titleSaved ? match ($tStatus['key']) {
        'approved'           => 'success',
        'revision_requested' => 'warning',
        'waiting_response'   => 'info',
        'stored'             => 'primary',
        default              => 'gray',
    } : 'gray';
@endphp

<x-nuir-workspace-accordion
    wire:key="title-card-{{ $this->submission?->id ?? 'new' }}"
    :collapsible="false"
    :color="$tAccordionColor"
>
    <x-slot:header>
        <div class="flex w-full flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Judul</h3>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($tPerGuide as $pg)
                    <x-filament::badge :color="$pg['color']" size="sm">{{ $pg['label'] }}</x-filament::badge>
                @endforeach
                @if ($tBadge && empty($tPerGuide))
                    <x-filament::badge :color="$tStatus['color']" size="sm">{{ $tStatus['label'] }}</x-filament::badge>
                @endif
                @if ($tLast)
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500">{{ $tLast }}</span>
                @endif
            </div>
        </div>
    </x-slot:header>

    @if (! $this->titleSaved)
        <p class="mb-3 rounded-lg border border-info-200 bg-info-50 px-3 py-2 text-sm text-info-800 dark:border-info-500/30 dark:bg-info-950/40 dark:text-info-200">
            Isi dan simpan judul penelitian terlebih dahulu. Bagian usulan calon pembimbing, komponen NUI, dan referensi akan ditampilkan setelah judul berhasil disimpan.
        </p>
    @endif

    {{-- per-guide revision notes pinned at top (same style as NUI) --}}
    @if ($tGuideRevNotes->isNotEmpty())
        <div class="mb-3 rounded-md border border-warning-300 bg-warning-50 px-3 py-2.5 dark:border-warning-600 dark:bg-warning-950/40">
            <p class="mb-1.5 text-xs font-semibold text-warning-800 dark:text-warning-200">Catatan Revisi</p>
            <div class="space-y-1">
                @foreach ($tGuideRevNotes as $grn)
                    <p class="text-sm">
                        <span class="font-semibold text-warning-700 dark:text-warning-300">{{ $grn['label'] }}:</span>
                        <span class="italic text-gray-600 dark:text-gray-300">{{ $grn['note'] }}</span>
                    </p>
                @endforeach
            </div>
        </div>
    @elseif ($tLatestRevNote && filled($tLatestRevNote['note']))
        <div class="mb-3 rounded-md border border-warning-300 bg-warning-50 px-3 py-2.5 dark:border-warning-600 dark:bg-warning-950/40">
            <p class="mb-1 flex flex-wrap items-center gap-x-1.5 text-xs font-semibold text-warning-800 dark:text-warning-200">
                <span>Catatan Revisi DBS</span>
                @if ($tLatestRevNote['actor_name'])
                    <span class="opacity-60">·</span>
                    <span class="font-normal">{{ $tLatestRevNote['actor_name'] }}</span>
                @endif
                @if ($tLatestRevNote['recorded_at'])
                    <span class="opacity-60">·</span>
                    <span class="font-normal opacity-70"><x-nuir.human-date :date="$tLatestRevNote['recorded_at']" /></span>
                @endif
            </p>
            <p class="text-sm italic text-gray-600 dark:text-gray-300">{{ $tLatestRevNote['note'] }}</p>
        </div>
    @endif

    @if ($tStatus['key'] === 'approved')
        {{-- approved: plain text with success background --}}
        <div class="rounded-lg border border-success-200 bg-success-50 px-3 py-2.5 text-sm leading-relaxed whitespace-pre-wrap text-success-900 dark:border-success-700 dark:bg-success-950/40 dark:text-success-100">{{ $this->titleField }}</div>
    @elseif ($tRo && filled($this->titleField))
        {{-- readonly with content: plain div, avoids textarea height issues --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm leading-relaxed whitespace-pre-wrap text-gray-800 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">{{ $this->titleField }}</div>
    @else
        {{-- editable / compose / revision: textarea with word count and auto-resize --}}
        <div
            class="space-y-2"
            x-data="{
                c: {{ $tWc }},
                min: {{ $tMin !== null ? $tMin : 'null' }},
                max: {{ $tMax !== null ? $tMax : 'null' }},
                ok() {
                    return (this.min === null || this.c >= this.min)
                        && (this.max === null || this.c <= this.max);
                },
                resize(el) {
                    el.style.height = 'auto';
                    el.style.height = el.scrollHeight + 'px';
                }
            }"
            x-init="$nextTick(() => $el.querySelectorAll('textarea').forEach(t => resize(t)))"
            @input="
                if ($event.target.tagName === 'TEXTAREA') {
                    c = ($event.target.value.trim() === '') ? 0 : $event.target.value.trim().split(/\s+/).length;
                    resize($event.target);
                }
            "
        >
            <div class="flex flex-wrap items-center gap-2">
                @if ($tHint)
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $tHint }}</span>
                @endif
                <span
                    x-show="c > 0"
                    x-cloak
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    x-bind:class="ok()
                        ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300'
                        : 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300'"
                    x-text="c + ' kata'"
                ></span>
            </div>

            <textarea
                wire:model="titleField"
                rows="1"
                placeholder="Ketik judul penelitian di sini…"
                class="nuir-auto block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100"
            >{{ $this->titleField }}</textarea>
        </div>
    @endif

    {{-- buttons --}}
    @if ($tSave || $tEdit || $tCancel)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            @if ($tSave)
                <x-filament::button
                    type="button"
                    size="sm"
                    icon="heroicon-m-check"
                    wire:click="saveTitleField"
                    wire:loading.attr="disabled"
                    wire:target="saveTitleField"
                >
                    {{ $tUi['saveLabel'] }}
                </x-filament::button>
            @endif
            @if ($tCancel)
                <x-filament::button
                    type="button"
                    size="sm"
                    color="gray"
                    icon="heroicon-m-x-mark"
                    wire:click="cancelTitleEdit"
                    wire:loading.attr="disabled"
                    wire:target="cancelTitleEdit"
                >
                    Batal
                </x-filament::button>
            @endif
            @if ($tEdit)
                <x-filament::button
                    type="button"
                    size="sm"
                    color="gray"
                    icon="heroicon-m-pencil-square"
                    wire:click="beginTitleEdit"
                    wire:loading.attr="disabled"
                    wire:target="beginTitleEdit"
                >
                    {{ $tUi['editLabel'] }}
                </x-filament::button>
            @endif
        </div>
    @endif

    @include('filament.mahasiswa.pages.partials.revision-accordion', [
        'history' => $tHistory->all(),
    ])
</x-nuir-workspace-accordion>


@if ($this->titleSaved)

{{-- ═══════════════════════════════════════════════════════
     CARD USULAN CALON PEMBIMBING
     ═══════════════════════════════════════════════════════ --}}
<x-filament::section heading="Usulan Calon Pembimbing">
    <div class="grid items-start gap-4 md:grid-cols-2">
        @foreach ([1, 2] as $seat)
            @php
                $seatState      = $this->guideSeatState($seat);
                $otherSeatState = $this->guideSeatState($seat === 1 ? 2 : 1);
                $selModel       = $seat === 1 ? 'guide1Selection' : 'guide2Selection';
                $lecturerOptions = $this->guideLecturerOptions($seat);
                $selectedGuide  = collect($lecturerOptions)->firstWhere('id', $seatState['guide_id']);
                $excludeId      = ($otherSeatState['status'] !== 'rejected')
                                    ? $otherSeatState['guide_id']
                                    : null;
            @endphp

            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Calon Pembimbing {{ $seat }}
                </h3>

                @if ($seatState['is_readonly'] && $selectedGuide)
                    <p class="mb-1 text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $selectedGuide['name'] }}
                    </p>
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                        Sisa kuota P{{ $seat }}: {{ $selectedGuide['remaining_quota'] }} ·
                        tidak dapat diubah kecuali pembimbing menolak usulan.
                    </p>
                    @php
                        $seatPresent = \App\Support\NuirSeatStatusPresenter::present($seatState['status']);
                    @endphp
                    <x-filament::badge :color="$seatPresent['color']">
                        {{ $seatPresent['label'] }}
                    </x-filament::badge>
                    @if ($seatState['note'])
                        <p class="mt-2 text-xs text-danger-700 dark:text-danger-400">
                            {{ $seatState['note'] }}
                        </p>
                    @endif

                    @if ($seatState['can_cancel'])
                        <x-filament::button
                            type="button"
                            size="sm"
                            color="danger"
                            outlined
                            icon="heroicon-m-x-circle"
                            class="mt-3"
                            wire:click="cancelGuide({{ $seat }})"
                            wire:loading.attr="disabled"
                            wire:target="cancelGuide({{ $seat }})"
                            wire:confirm="Batalkan usulan Pembimbing {{ $seat }} ini?"
                        >
                            Batalkan Usulan
                        </x-filament::button>
                    @endif

                @elseif ($seatState['can_change'])
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Pilih dosen dengan sisa kuota P{{ $seat }} &gt; 0. Angka kuota diperbarui sesuai data terkini.
                        </p>
                        <select
                            wire:model.live="{{ $selModel }}"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-950"
                        >
                            <option value="">— Pilih dosen —</option>
                            @foreach ($lecturerOptions as $option)
                                @if ($option['id'] === $excludeId)
                                    @continue
                                @endif
                                <option
                                    value="{{ $option['id'] }}"
                                    @disabled(! $option['selectable'])
                                >
                                    {{ $option['name'] }} — sisa kuota P{{ $seat }}: {{ $option['remaining_quota'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-filament::button
                            type="button"
                            size="sm"
                            icon="heroicon-m-paper-airplane"
                            wire:click="proposeGuide({{ $seat }})"
                            wire:loading.attr="disabled"
                            wire:target="proposeGuide"
                        >
                            Ajukan Pembimbing {{ $seat }}
                        </x-filament::button>
                    </div>
                @endif

                @include('filament.mahasiswa.pages.partials.proposal-history', [
                    'history' => $this->proposalSeatHistory($seat),
                    'seat'    => $seat,
                ])
            </div>
        @endforeach
    </div>
</x-filament::section>

@php
    $documentLinkHref = \App\Support\NuirExternalUrl::normalize($this->nuirDocumentLink);
@endphp

<p class="mt-1 text-center">
    <button
        type="button"
        wire:click="toggleDocumentCard"
        class="text-xs text-gray-500 underline decoration-dotted underline-offset-2 transition hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
    >
        Lampirkan dokumen NUIR (Google Drive)
    </button>
</p>

@if ($this->showDocumentCard)
    <x-filament::section heading="Dokumen NUIR" class="mt-3">
        <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
            Opsional. Tempel tautan Google Drive ke dokumen NUIR yang sudah Anda siapkan.
            Pastikan akses file diatur agar pembimbing dan validator dapat membuka tautan.
        </p>

        <div class="space-y-3">
            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Link Google Drive</label>
                    @if ($documentLinkHref)
                        <a
                            href="{{ $documentLinkHref }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400"
                        >
                            Buka tautan &nearr;
                        </a>
                    @endif
                </div>
                <textarea
                    wire:model="nuirDocumentLink"
                    rows="2"
                    placeholder="https://drive.google.com/file/d/…/view?usp=sharing"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100"
                ></textarea>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button
                    type="button"
                    size="sm"
                    icon="heroicon-m-link"
                    wire:click="saveDocumentLink"
                    wire:loading.attr="disabled"
                    wire:target="saveDocumentLink"
                >
                    Simpan Link Dokumen
                </x-filament::button>

                @if (filled($this->nuirDocumentLink))
                    <x-filament::button
                        type="button"
                        size="sm"
                        color="gray"
                        wire:click="$set('nuirDocumentLink', '')"
                    >
                        Kosongkan
                    </x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>
@endif


{{-- ═══════════════════════════════════════════════════════
     CARD KOMPONEN NUIR
     ═══════════════════════════════════════════════════════ --}}
<x-filament::section heading="Komponen NUIR">
    <p class="mb-4 rounded-lg border border-info-200 bg-info-50 px-3 py-2 text-sm text-info-800 dark:border-info-500/30 dark:bg-info-950/40 dark:text-info-200">
        Isi dan simpan setiap komponen NUI (Novelty, Urgency, dan Impact) secara terpisah.
    </p>

    <div class="space-y-4">
        @foreach ([
            'novelty' => ['label' => 'Novelty', 'model' => 'noveltyField', 'save' => 'saveNoveltyField'],
            'urgency' => ['label' => 'Urgency', 'model' => 'urgencyField', 'save' => 'saveUrgencyField'],
            'impact'  => ['label' => 'Impact',  'model' => 'impactField',  'save' => 'saveImpactField'],
        ] as $nKey => $nMeta)
            @php
                $nLimits        = $limits[$nKey] ?? [];
                $nMin           = $nLimits['min'] ?? null;
                $nMax           = $nLimits['max'] ?? null;
                $nHint          = $this->wordLimitHint($nKey);
                $nVal           = $this->{$nMeta['model']};
                $nWc            = NuirTextLimits::wordCount($nVal);
                $nUi            = $this->workspaceFieldUi($nKey, $nMeta['label']);
                $nEditing       = $this->editingField === $nKey;
                $nRo            = $nUi['readonly'] && ! $nEditing;
                $nSave          = in_array($nUi['action'], ['compose', 'revision'], true)
                                  || ($nUi['action'] === 'edit' && $nEditing);
                $nEdit          = $nUi['action'] === 'edit' && $nUi['showEdit'] && ! $nEditing;
                $nCancel        = $nUi['action'] === 'edit' && $nEditing;
                $nStatus        = NuirMahasiswaFieldStatus::nuiFieldStatus($this->submission, $proposal, $nKey);
                // Only show status badge if the field has been saved at least once
                $nBadge         = NuirMahasiswaFieldStatus::isWorkflowBadge($nStatus) && filled($nVal);
                $nLast          = NuirMahasiswaFieldStatus::nuiFieldLastModified($this->submission, $nKey);
                $nHistory       = $this->fieldHistory($nKey);
                // Per-guide approval status (only for NUI fields when proposal exists)
                $nPerGuide      = NuirMahasiswaFieldStatus::perGuideFieldStatuses($this->submission, $proposal, $nKey);
                // Per-guide revision notes (P1/P2) for the pinned banner
                $nGuideRevNotes = ($nStatus['key'] === 'revision_requested' && $this->submission)
                    ? $this->submission->contentReviews
                        ->where('field', $nKey)
                        ->where('approved', false)
                        ->whereIn('role', [NuirContentReview::ROLE_GUIDE1, NuirContentReview::ROLE_GUIDE2])
                        ->sortBy(fn ($r) => $r->role === NuirContentReview::ROLE_GUIDE1 ? 0 : 1)
                        ->map(fn ($r) => [
                            'label' => $r->role === NuirContentReview::ROLE_GUIDE1 ? 'P1' : 'P2',
                            'note'  => $r->note,
                        ])
                        ->filter(fn ($r) => filled($r['note']))
                        ->values()
                    : collect();
                // Accordion: open when empty, revision requested, or has revision history (even if approved)
                $nOpen          = ! filled($nVal) || $nStatus['key'] === 'revision_requested' || $nHistory->isNotEmpty();
                // Border color mapped per status key (revision → warning per UX intent)
                $nAccordionColor = filled($nVal) ? match ($nStatus['key']) {
                    'approved'           => 'success',
                    'revision_requested' => 'warning',
                    'waiting_response'   => 'info',
                    'stored'             => 'primary',
                    default              => 'gray',
                } : 'gray';
            @endphp

            <x-nuir-workspace-accordion
                wire:key="nui-card-{{ $nKey }}-{{ $this->submission?->id ?? 'new' }}-{{ $this->nuiSavedStamp($nKey) }}"
                :default-open="$nOpen"
                :color="$nAccordionColor"
            >
                <x-slot:header>
                    <div class="flex w-full flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $nMeta['label'] }}
                        </h3>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($nPerGuide as $pg)
                                <x-filament::badge :color="$pg['color']" size="sm">{{ $pg['label'] }}</x-filament::badge>
                            @endforeach
                            @if ($nBadge && empty($nPerGuide))
                                <x-filament::badge :color="$nStatus['color']" size="sm">
                                    {{ $nStatus['label'] }}
                                </x-filament::badge>
                            @endif
                            @if ($nLast)
                                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">{{ $nLast }}</span>
                            @endif
                        </div>
                    </div>
                </x-slot:header>

                {{-- per-guide revision notes pinned at top --}}
                @if ($nGuideRevNotes->isNotEmpty())
                    <div class="mb-3 rounded-md border border-warning-300 bg-warning-50 px-3 py-2.5 dark:border-warning-600 dark:bg-warning-950/40">
                        <p class="mb-1.5 text-xs font-semibold text-warning-800 dark:text-warning-200">Catatan Revisi</p>
                        <div class="space-y-1">
                            @foreach ($nGuideRevNotes as $grn)
                                <p class="text-sm">
                                    <span class="font-semibold text-warning-700 dark:text-warning-300">{{ $grn['label'] }}:</span>
                                    <span class="italic text-gray-600 dark:text-gray-300">{{ $grn['note'] }}</span>
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($nStatus['key'] === 'approved')
                    {{-- approved: plain text with success background --}}
                    <div class="rounded-lg border border-success-200 bg-success-50 px-3 py-2.5 text-sm leading-relaxed whitespace-pre-wrap text-success-900 dark:border-success-700 dark:bg-success-950/40 dark:text-success-100">{{ $nVal }}</div>
                @elseif ($nRo && filled($nVal))
                    {{-- readonly with content: plain div, avoids textarea height issues --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm leading-relaxed whitespace-pre-wrap text-gray-800 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">{{ $nVal }}</div>
                @else
                    {{-- editable / compose / revision: textarea with word count and auto-resize --}}
                    <div
                        class="space-y-2"
                        x-data="{
                            c: {{ $nWc }},
                            min: {{ $nMin !== null ? $nMin : 'null' }},
                            max: {{ $nMax !== null ? $nMax : 'null' }},
                            ok() {
                                return (this.min === null || this.c >= this.min)
                                    && (this.max === null || this.c <= this.max);
                            },
                            resize(el) {
                                el.style.height = 'auto';
                                el.style.height = el.scrollHeight + 'px';
                            }
                        }"
                        x-init="$nextTick(() => $el.querySelectorAll('textarea').forEach(t => resize(t)))"
                        @input="
                            if ($event.target.tagName === 'TEXTAREA') {
                                c = ($event.target.value.trim() === '') ? 0 : $event.target.value.trim().split(/\s+/).length;
                                resize($event.target);
                            }
                        "
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($nHint)
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $nHint }}</span>
                            @endif
                            <span
                                x-show="c > 0"
                                x-cloak
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                x-bind:class="ok()
                                    ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300'
                                    : 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300'"
                                x-text="c + ' kata'"
                            ></span>
                        </div>

                        <textarea
                            wire:model="{{ $nMeta['model'] }}"
                            rows="1"
                            placeholder="Ketik {{ $nMeta['label'] }} penelitian di sini…"
                            class="nuir-auto block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100"
                        >{{ $nVal }}</textarea>
                    </div>
                @endif

                {{-- buttons --}}
                @if ($nSave || $nEdit || $nCancel)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($nSave)
                            <x-filament::button
                                type="button"
                                size="sm"
                                icon="heroicon-m-check"
                                wire:click="{{ $nMeta['save'] }}"
                                wire:loading.attr="disabled"
                                wire:target="{{ $nMeta['save'] }}"
                            >
                                {{ $nUi['saveLabel'] }}
                            </x-filament::button>
                        @endif
                        @if ($nCancel)
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                icon="heroicon-m-x-mark"
                                wire:click="cancelFieldEdit"
                                wire:loading.attr="disabled"
                                wire:target="cancelFieldEdit"
                            >
                                Batal
                            </x-filament::button>
                        @endif
                        @if ($nEdit)
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                icon="heroicon-m-pencil-square"
                                wire:click="beginFieldEdit('{{ $nKey }}')"
                                wire:loading.attr="disabled"
                                wire:target="beginFieldEdit"
                            >
                                {{ $nUi['editLabel'] }}
                            </x-filament::button>
                        @endif
                    </div>
                @endif

                @include('filament.mahasiswa.pages.partials.revision-accordion', [
                    'history' => $nHistory->all(),
                ])
            </x-nuir-workspace-accordion>

        @endforeach
    </div>
</x-filament::section>


{{-- ═══════════════════════════════════════════════════════
     CARD REFERENSI
     ═══════════════════════════════════════════════════════ --}}
<x-filament::section heading="Referensi">
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Isi dan simpan setiap slot referensi secara terpisah.
    </p>

    <div class="space-y-4">
        @foreach ($this->referenceSlots as $rOrder)
            @php
                $ref      = $this->submission->references->firstWhere('ref_order', $rOrder);
                $rStatus  = NuirMahasiswaFieldStatus::referenceStatus($ref);
                $rBadge   = NuirMahasiswaFieldStatus::isWorkflowBadge($rStatus);
                $rLast    = NuirMahasiswaFieldStatus::referenceLastModified($ref);
                $rHistory = $this->referenceHistory($rOrder);
                $rEdit    = $ref?->ref_approved !== true;
                $rFields  = $this->referenceFields[$rOrder] ?? [];
            @endphp

            <x-nuir-workspace-accordion
                wire:key="ref-card-{{ $rOrder }}-{{ $this->submission->id }}-{{ $ref?->updated_at?->timestamp ?? 0 }}"
                :default-open="$this->referenceSlotAccordionOpen($rOrder)"
            >
                <x-slot:header>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Referensi #{{ $rOrder }}
                        </h3>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($rBadge)
                                <x-filament::badge :color="$rStatus['color']" size="sm">
                                    {{ $rStatus['label'] }}
                                </x-filament::badge>
                            @endif
                            @if ($rLast)
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $rLast }}</span>
                            @endif
                        </div>
                    </div>
                </x-slot:header>

                @if ($ref?->ref_note && $ref->ref_approved === false)
                    <div class="mb-3 rounded-md bg-danger-50 px-3 py-2 text-sm text-danger-800 dark:bg-danger-950/40 dark:text-danger-200">
                        @if (filled($ref->ref_revision_fields))
                            <p>
                                <span class="font-medium">Bagian diperbaiki:</span>
                                {{ NuirReferenceRevisionFields::labelsText($ref->ref_revision_fields) }}
                            </p>
                        @endif
                        <p><span class="font-medium">Catatan:</span> {{ $ref->ref_note }}</p>
                    </div>
                @endif

                @php
                    $indexerOptions = [
                        'Sinta 1', 'Sinta 2', 'Sinta 3', 'Sinta 4', 'Sinta 5', 'Sinta 6',
                        'Scopus', 'Web of Science', 'IEEE Xplore',
                        'Springer', 'Elsevier', 'Emerald', 'DOAJ', 'Lainnya',
                    ];
                @endphp

                @if (! $rEdit)
                    {{-- readonly (approved): plain text, links are clickable --}}
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ([
                            'link_ojs'   => 'Link OJS',
                            'link_index' => 'Link Index',
                            'link_drive' => 'Link Drive',
                        ] as $rf => $rl)
                            @if (filled($rFields[$rf] ?? ''))
                                <div>
                                    <p class="mb-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $rl }}</p>
                                    <a href="{{ $rFields[$rf] }}" target="_blank" rel="noopener noreferrer"
                                       class="break-all text-sm text-primary-600 underline hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                        {{ $rFields[$rf] }}
                                    </a>
                                </div>
                            @endif
                        @endforeach

                        @if (filled($rFields['indexer_name'] ?? ''))
                            <div>
                                <p class="mb-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">Nama Indexer</p>
                                <p class="text-sm text-gray-800 dark:text-gray-200">{{ $rFields['indexer_name'] }}</p>
                            </div>
                        @endif

                        @if (filled($rFields['quote'] ?? ''))
                            <div class="md:col-span-2">
                                <p class="mb-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">Kutipan</p>
                                <p class="text-sm leading-relaxed text-gray-800 dark:text-gray-200">{{ $rFields['quote'] }}</p>
                            </div>
                        @endif

                        @if (filled($rFields['relevance'] ?? ''))
                            <div class="md:col-span-2">
                                <p class="mb-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">Relevansi</p>
                                <p class="text-sm leading-relaxed text-gray-800 dark:text-gray-200">{{ $rFields['relevance'] }}</p>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- editable: textareas + indexer select + open-link buttons --}}
                    <div
                        class="space-y-3"
                        x-data="{
                            resize(el) {
                                el.style.height = 'auto';
                                el.style.height = el.scrollHeight + 'px';
                            },
                            resizeAll() {
                                this.$el.querySelectorAll('textarea').forEach(t => this.resize(t));
                            }
                        }"
                        x-init="$nextTick(() => resizeAll())"
                        @input="if ($event.target.tagName === 'TEXTAREA') resize($event.target)"
                        @nuir-accordion-opened.window="$nextTick(() => resizeAll())"
                    >
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Link OJS</label>
                                    @if (filled($rFields['link_ojs'] ?? ''))
                                        <a href="{{ $rFields['link_ojs'] }}" target="_blank" rel="noopener noreferrer"
                                           class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400">
                                            Buka &nearr;
                                        </a>
                                    @endif
                                </div>
                                <textarea
                                    wire:model="referenceFields.{{ $rOrder }}.link_ojs"
                                    rows="1"
                                    class="nuir-auto block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-950"
                                >{{ $rFields['link_ojs'] ?? '' }}</textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Indexer</label>
                                <select
                                    wire:model="referenceFields.{{ $rOrder }}.indexer_name"
                                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100"
                                >
                                    <option value="">-- Pilih Indexer --</option>
                                    @foreach ($indexerOptions as $opt)
                                        <option value="{{ $opt }}" @selected(($rFields['indexer_name'] ?? '') === $opt)>{{ $opt }}</option>
                                    @endforeach
                                    @if (filled($rFields['indexer_name'] ?? '') && ! in_array($rFields['indexer_name'], $indexerOptions))
                                        <option value="{{ $rFields['indexer_name'] }}" selected>{{ $rFields['indexer_name'] }}</option>
                                    @endif
                                </select>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Link Index</label>
                                    @if (filled($rFields['link_index'] ?? ''))
                                        <a href="{{ $rFields['link_index'] }}" target="_blank" rel="noopener noreferrer"
                                           class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400">
                                            Buka &nearr;
                                        </a>
                                    @endif
                                </div>
                                <textarea
                                    wire:model="referenceFields.{{ $rOrder }}.link_index"
                                    rows="1"
                                    class="nuir-auto block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-950"
                                >{{ $rFields['link_index'] ?? '' }}</textarea>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Link Drive</label>
                                    @if (filled($rFields['link_drive'] ?? ''))
                                        <a href="{{ $rFields['link_drive'] }}" target="_blank" rel="noopener noreferrer"
                                           class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400">
                                            Buka &nearr;
                                        </a>
                                    @endif
                                </div>
                                <textarea
                                    wire:model="referenceFields.{{ $rOrder }}.link_drive"
                                    rows="1"
                                    class="nuir-auto block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-950"
                                >{{ $rFields['link_drive'] ?? '' }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kutipan</label>
                                <textarea
                                    wire:model="referenceFields.{{ $rOrder }}.quote"
                                    rows="1"
                                    class="nuir-auto block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-950"
                                >{{ $rFields['quote'] ?? '' }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Relevansi</label>
                                <textarea
                                    wire:model="referenceFields.{{ $rOrder }}.relevance"
                                    rows="1"
                                    class="nuir-auto block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-950"
                                >{{ $rFields['relevance'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <x-filament::button
                            type="button"
                            size="sm"
                            icon="heroicon-m-bookmark-square"
                            wire:click="saveReference({{ $rOrder }})"
                            wire:loading.attr="disabled"
                            wire:target="saveReference"
                        >
                            Simpan Referensi #{{ $rOrder }}
                        </x-filament::button>
                    </div>
                @endif

                @include('filament.mahasiswa.pages.partials.revision-accordion', [
                    'history'    => $rHistory->all(),
                    'emptyLabel' => 'Belum ada histori revisi referensi.',
                ])
            </x-nuir-workspace-accordion>
        @endforeach
    </div>
</x-filament::section>

@endif {{-- titleSaved --}}


{{-- ═══════════════════════════════════════════════════════
     HISTORI PENOLAKAN
     ═══════════════════════════════════════════════════════ --}}
@if ($this->submission && $this->rejectionHistory->isNotEmpty())
    <x-filament::section heading="Histori Penolakan Usulan">
        @include('filament.mahasiswa.pages.partials.rejection-accordion', [
            'history'  => $this->rejectionHistory->all(),
            'expanded' => true,
        ])
    </x-filament::section>
@endif

@endif {{-- not stage3/closed --}}

</div>
</x-filament-panels::page>
