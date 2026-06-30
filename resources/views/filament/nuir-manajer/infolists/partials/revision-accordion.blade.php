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
                <div
                    x-data="{ itemOpen: {{ $index === 0 ? 'true' : 'false' }} }"
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white/80 dark:border-gray-700 dark:bg-gray-950/40"
                >
                    <button
                        type="button"
                        @click="itemOpen = ! itemOpen"
                        class="flex w-full items-start justify-between gap-3 px-3 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-900/50"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['heading'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                @if ($item['recorded_at'])
                                    {{ $item['recorded_at']->format('d M Y H:i') }}
                                @endif
                                @if ($item['actor_name'])
                                    · {{ $item['actor_name'] }}
                                @endif
                            </p>
                        </div>
                        <x-filament::icon
                            icon="heroicon-m-chevron-down"
                            class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition-transform"
                            x-bind:class="itemOpen ? 'rotate-180' : ''"
                        />
                    </button>

                    <div x-show="itemOpen" x-collapse class="border-t border-gray-100 px-3 py-2.5 text-sm dark:border-gray-800">
                        @if (filled($item['note']))
                            <p class="mb-2 rounded-md bg-warning-50 px-2.5 py-2 text-warning-900 dark:bg-warning-950/40 dark:text-warning-200">
                                <span class="font-medium">Catatan:</span> {{ $item['note'] }}
                            </p>
                        @endif

                        @if (filled($item['content']))
                            <div class="rounded-md bg-gray-50 px-2.5 py-2 text-gray-700 whitespace-pre-wrap dark:bg-gray-900/60 dark:text-gray-300">
                                {{ $item['content'] }}
                            </div>
                        @elseif (blank($item['note']))
                            <p class="text-xs italic text-gray-400">Tidak ada detail tambahan.</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
