@php
    use App\Support\NuirReferenceExistence;

    $histories = $histories ?? [];
    $revisionRounds = $revisionRounds ?? [];
    $showRevisionBadges = $showRevisionBadges ?? [];
    $canReview = $canReview ?? false;
    $revisionFieldOptions = $revisionFieldOptions ?? [];
@endphp

<div class="space-y-2">
    @forelse ($references as $reference)
        @php
            $history = $histories[$reference->ref_order] ?? [];
            $revisionRound = $revisionRounds[$reference->ref_order] ?? 1;
            $showRevisionBadge = $showRevisionBadges[$reference->ref_order] ?? false;
            $isVerifiable = NuirReferenceExistence::isVerifiable($reference);
            $statusLabel = match ($reference->ref_approved) {
                true => 'Disetujui',
                false => 'Diminta Revisi',
                default => 'Pending',
            };
            $statusColor = match ($reference->ref_approved) {
                true => 'success',
                false => 'danger',
                default => 'gray',
            };
        @endphp

        <div
            wire:key="dosen-reference-{{ $reference->id }}"
            x-data="{ open: false }"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-950/40"
        >
            <button
                type="button"
                x-on:click="open = ! open"
                class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left transition hover:bg-teal-50/70 dark:hover:bg-teal-950/20"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <x-filament::icon
                        icon="heroicon-m-chevron-down"
                        class="mt-0.5 h-5 w-5 shrink-0 text-gray-500 transition-transform dark:text-gray-400"
                        x-bind:class="open ? 'rotate-180' : ''"
                    />
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                Referensi #{{ $reference->ref_order }}
                            </span>
                            @if ($showRevisionBadge)
                                <x-filament::badge color="info">Revisi ke-{{ $revisionRound }}</x-filament::badge>
                            @endif
                            <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                            <x-filament::badge :color="$isVerifiable ? 'success' : 'warning'">
                                {{ $isVerifiable ? 'Lengkap' : 'Belum lengkap' }}
                            </x-filament::badge>
                        </div>
                        @if ($reference->indexer_name)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $reference->indexer_name }}</p>
                        @elseif ($reference->quote)
                            <p class="mt-1 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">{{ $reference->quote }}</p>
                        @endif
                    </div>
                </div>
                <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400" x-text="open ? 'Tutup' : 'Buka'"></span>
            </button>

            <div
                x-show="open"
                x-collapse
                class="border-t border-gray-100 px-4 py-3 dark:border-gray-800"
            >
                <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="grid gap-2 md:grid-cols-2">
                        @if ($reference->link_ojs)
                            <div class="rounded-lg border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-800 dark:bg-gray-900/50">
                                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Link OJS</p>
                                <a href="{{ $reference->link_ojs }}" target="_blank" rel="noopener noreferrer" class="break-all text-teal-700 hover:underline dark:text-teal-300">
                                    {{ $reference->link_ojs }}
                                </a>
                            </div>
                        @endif
                        @if ($reference->link_index)
                            <div class="rounded-lg border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-800 dark:bg-gray-900/50">
                                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Link Index</p>
                                <a href="{{ $reference->link_index }}" target="_blank" rel="noopener noreferrer" class="break-all text-teal-700 hover:underline dark:text-teal-300">
                                    {{ $reference->link_index }}
                                </a>
                            </div>
                        @endif
                        @if ($reference->link_drive)
                            <div class="rounded-lg border border-gray-100 bg-gray-50/80 p-3 dark:border-gray-800 dark:bg-gray-900/50 md:col-span-2">
                                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Link Drive</p>
                                <a href="{{ $reference->link_drive }}" target="_blank" rel="noopener noreferrer" class="break-all text-teal-700 hover:underline dark:text-teal-300">
                                    {{ $reference->link_drive }}
                                </a>
                            </div>
                        @endif
                    </div>

                    @if ($reference->quote)
                        <div class="rounded-lg border border-teal-100 bg-teal-50/50 p-3 dark:border-teal-900 dark:bg-teal-950/20">
                            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-teal-700 dark:text-teal-300">Kutipan (terakhir)</p>
                            <p class="whitespace-pre-wrap">{{ $reference->quote }}</p>
                        </div>
                    @endif

                    @if ($reference->relevance)
                        <div class="rounded-lg border border-teal-100 bg-teal-50/50 p-3 dark:border-teal-900 dark:bg-teal-950/20">
                            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-teal-700 dark:text-teal-300">Relevansi (terakhir)</p>
                            <p class="whitespace-pre-wrap">{{ $reference->relevance }}</p>
                        </div>
                    @endif

                    @if ($reference->ref_note && $reference->ref_approved === false)
                        <div class="space-y-2 rounded-md bg-danger-50 px-3 py-2 text-danger-800 dark:bg-danger-950/40 dark:text-danger-200">
                            @if (filled($reference->ref_revision_fields))
                                <p>
                                    <span class="font-medium">Bagian diperbaiki:</span>
                                    {{ \App\Support\NuirReferenceRevisionFields::labelsText($reference->ref_revision_fields) }}
                                </p>
                            @endif
                            <p>
                                <span class="font-medium">Catatan saat ini:</span> {{ $reference->ref_note }}
                            </p>
                        </div>
                    @endif

                    @if ($canReview)
                        <div
                            x-data="{
                                revisionOpen: false,
                                note: @js($reference->ref_note ?? ''),
                                fields: @js($reference->ref_revision_fields ?? []),
                                fieldOptions: @js($revisionFieldOptions),
                                toggleField(key, label, checked) {
                                    const prefix = '(' + label + '):';

                                    if (checked) {
                                        if (! this.fields.includes(key)) {
                                            this.fields.push(key);
                                        }

                                        const hasLine = this.note.split('\n').some(line => line.trim().startsWith(prefix));

                                        if (! hasLine) {
                                            this.note = this.note.replace(/\s+$/, '') === ''
                                                ? prefix + ' '
                                                : this.note.replace(/\n*$/, '') + '\n' + prefix + ' ';
                                        }
                                    } else {
                                        this.fields = this.fields.filter(f => f !== key);
                                        this.note = this.note
                                            .split('\n')
                                            .filter(line => ! line.trim().startsWith(prefix))
                                            .join('\n');
                                    }
                                },
                            }"
                            class="space-y-3 border-t border-gray-100 pt-3 dark:border-gray-800"
                        >
                            <div
                                x-show="revisionOpen"
                                x-cloak
                                x-collapse
                                class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                            >
                                <label class="mb-2 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Bagian yang perlu diperbaiki
                                </label>
                                <div class="mb-3 space-y-1.5 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                    <template x-for="(label, key) in fieldOptions" :key="key">
                                        <label class="flex cursor-pointer items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-950"
                                                :value="key"
                                                :checked="fields.includes(key)"
                                                x-on:change="toggleField(key, label, $event.target.checked)"
                                            />
                                            <span x-text="label"></span>
                                        </label>
                                    </template>
                                </div>

                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Catatan revisi
                                </label>
                                <textarea
                                    x-model="note"
                                    rows="4"
                                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100"
                                    placeholder="Centang bagian di atas untuk menambahkan barisnya secara otomatis, contoh: (Link OJS): catatannya"
                                ></textarea>
                                <div class="mt-3 flex justify-end gap-2">
                                    <x-filament::button color="gray" size="sm" x-on:click="revisionOpen = false">
                                        Batal
                                    </x-filament::button>
                                    <x-filament::button
                                        color="warning"
                                        size="sm"
                                        x-on:click="$wire.requestReferenceRevision({{ $reference->id }}, note, fields).then(() => revisionOpen = false)"
                                    >
                                        Kirim
                                    </x-filament::button>
                                </div>
                            </div>

                            <div x-show="! revisionOpen" class="flex flex-wrap items-center gap-2">
                                <x-filament::button
                                    color="warning"
                                    size="sm"
                                    icon="heroicon-o-pencil-square"
                                    x-on:click="revisionOpen = ! revisionOpen"
                                >
                                    Minta Revisi
                                </x-filament::button>
                            </div>
                        </div>
                    @endif

                    @include('filament.nuir-manajer.infolists.partials.revision-accordion', [
                        'history' => $history,
                    ])
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm italic text-gray-500">Belum ada referensi.</p>
    @endforelse
</div>
