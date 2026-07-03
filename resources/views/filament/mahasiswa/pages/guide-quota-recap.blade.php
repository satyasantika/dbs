<x-filament-panels::page>
    <div class="max-w-xs">
        <x-filament::input.wrapper>
            <x-filament::input.select disabled>
                <option value="{{ $yearGeneration }}">{{ $yearGeneration ?? '—' }}</option>
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    @if (blank($yearGeneration))
        <p class="text-sm italic text-gray-500">Angkatan Anda belum terdaftar.</p>
    @else
        {{ $this->table }}
    @endif
</x-filament-panels::page>
