<div x-data="{ open: false }" class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-800">
    <button
        type="button"
        @click="open = ! open"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
    >
        <x-filament::icon icon="heroicon-m-chevron-down" class="h-4 w-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
        @if (count($history) > 0)
            <span x-text="open ? 'Sembunyikan histori revisi' : 'Lihat histori revisi ({{ count($history) }})'"></span>
        @else
            <span x-text="open ? 'Sembunyikan histori revisi' : 'Lihat histori revisi'"></span>
        @endif
    </button>

    <div x-show="open" x-collapse class="mt-3 space-y-2">
        @forelse ($history as $index => $item)
            @include('filament.nuir-manajer.infolists.partials.revision-history-item', [
                'item' => $item,
                'index' => $index,
            ])
        @empty
            <p class="text-xs italic text-gray-500 dark:text-gray-400">{{ $emptyLabel ?? 'Belum ada histori revisi.' }}</p>
        @endforelse
    </div>
</div>
