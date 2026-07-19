{{-- Caption "Persetujuan" ditulis langsung di sini (bukan lewat ->label()
     kolom) supaya tetap terlihat di mode card grid, yang tidak menampilkan
     header kolom seperti tabel. --}}
<div>
    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Persetujuan</p>
    <div class="flex flex-col items-start gap-1">
        @foreach ($lines as $line)
            <x-filament::badge :color="$line['color']">
                {{ $line['label'] }}
            </x-filament::badge>
        @endforeach
    </div>
</div>
