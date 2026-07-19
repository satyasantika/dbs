{{-- Caption "Referensi" ditulis langsung di sini (bukan lewat ->label()
     kolom) supaya tetap terlihat di mode card grid, yang tidak menampilkan
     header kolom seperti tabel. --}}
<div>
    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Referensi</p>
    <div class="flex flex-wrap gap-1.5">
        @forelse ($badges as $badge)
            <x-filament::badge :color="$badge['color']">
                {{ $badge['label'] }}
            </x-filament::badge>
        @empty
            <span class="text-sm text-gray-500 dark:text-gray-400">Belum ada referensi</span>
        @endforelse
    </div>
</div>
