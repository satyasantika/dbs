<x-filament-panels::page>
    {{ $this->infolist }}

    <div wire:poll.15s.visible="pollRefresh"></div>
</x-filament-panels::page>
