<x-filament-widgets::widget>
    <x-filament::section heading="Status Pengajuan NUIR">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Ringkasan status Judul dan komponen NUI (Novelty, Urgency, Impact).
        </p>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getComponentStatuses() as $nuiComponent)
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $nuiComponent['label'] }}
                    </p>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($nuiComponent['perGuide'] as $guideStatus)
                            <x-filament::badge :color="$guideStatus['color']" size="sm">
                                {{ $guideStatus['label'] }}
                            </x-filament::badge>
                        @endforeach

                        @if ($nuiComponent['perGuide'] === [])
                            <x-filament::badge :color="$nuiComponent['status']['color']" size="sm">
                                {{ $nuiComponent['status']['label'] }}
                            </x-filament::badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <x-filament::button
                tag="a"
                href="{{ $this->workspaceUrl() }}"
                size="sm"
                icon="heroicon-m-arrow-top-right-on-square"
            >
                Buka Pengajuan NUIR
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
