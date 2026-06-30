@php
    $histories = $histories ?? [];
    $revisionRounds = $revisionRounds ?? [];
    $showRevisionBadges = $showRevisionBadges ?? [];
@endphp

<div class="space-y-3">
    @forelse ($references as $reference)
        @php
            $history = $histories[$reference->ref_order] ?? [];
            $revisionRound = $revisionRounds[$reference->ref_order] ?? 1;
            $showRevisionBadge = $showRevisionBadges[$reference->ref_order] ?? false;
            $statusLabel = match ($reference->ref_approved) {
                true => 'Disetujui',
                false => 'Ditolak',
                default => 'Pending',
            };
            $statusColor = match ($reference->ref_approved) {
                true => 'success',
                false => 'danger',
                default => 'gray',
            };
        @endphp

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-950/40">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Referensi #{{ $reference->ref_order }}
                        </span>
                        @if ($showRevisionBadge)
                            <x-filament::badge color="info">Revisi ke-{{ $revisionRound }}</x-filament::badge>
                        @endif
                        <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                    </div>
                    @if ($reference->indexer_name)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $reference->indexer_name }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-3 grid gap-2 text-sm text-gray-700 dark:text-gray-300">
                @if ($reference->link_ojs)
                    <p><span class="font-medium">OJS:</span> {{ $reference->link_ojs }}</p>
                @endif
                @if ($reference->link_index)
                    <p><span class="font-medium">Index:</span> {{ $reference->link_index }}</p>
                @endif
                @if ($reference->quote)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Kutipan (terakhir)</p>
                        <p class="whitespace-pre-wrap">{{ $reference->quote }}</p>
                    </div>
                @endif
                @if ($reference->relevance)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Relevansi (terakhir)</p>
                        <p class="whitespace-pre-wrap">{{ $reference->relevance }}</p>
                    </div>
                @endif
                @if ($reference->ref_note && $reference->ref_approved === false)
                    <p class="rounded-md bg-danger-50 px-2.5 py-2 text-danger-800 dark:bg-danger-950/40 dark:text-danger-200">
                        <span class="font-medium">Catatan validator saat ini:</span> {{ $reference->ref_note }}
                    </p>
                @endif
            </div>

            @include('filament.nuir-manajer.infolists.partials.revision-accordion', [
                'history' => $history,
            ])
        </div>
    @empty
        <p class="text-sm italic text-gray-500">Belum ada referensi.</p>
    @endforelse
</div>
