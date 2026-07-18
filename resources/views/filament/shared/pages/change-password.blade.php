<x-filament-panels::page>
    <x-filament-panels::form id="form" wire:submit="save">
        {{ $this->form }}

        <x-filament::button type="submit">
            Ubah Password
        </x-filament::button>
    </x-filament-panels::form>
</x-filament-panels::page>
