@if (count($history) > 0)
    <div x-data="{ open: false }" class="mt-3 border-t border-white/40 pt-3 dark:border-white/10">
        <button
            type="button"
            @click="open = ! open"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
        >
            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="h-4 w-4 transition-transform"
                x-bind:class="open ? 'rotate-180' : ''"
            />
            <span x-text="open ? 'Sembunyikan histori revisi' : 'Lihat histori revisi ({{ count($history) }})'"></span>
        </button>

        <div x-show="open" x-collapse class="mt-3 space-y-2">
            @foreach ($history as $index => $item)
                @include('filament.nuir-manajer.infolists.partials.revision-history-item', [
                    'item' => $item,
                    'index' => $index,
                ])
            @endforeach
        </div>
    </div>
@endif
