<x-filament-panels::page class="fi-dosen-scoring-page">
    <x-filament::section>
        <div class="flex flex-col gap-y-6">
            <x-filament-panels::resources.tabs />

            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-panels::page>
