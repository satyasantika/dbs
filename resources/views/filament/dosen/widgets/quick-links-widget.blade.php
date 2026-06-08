<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-2">
        @can('access examination/scoring')
            <x-filament::section heading="Penilaian Ujian">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    Untuk menilai ujian, silakan klik tombol berikut:
                </p>
                <x-filament::button
                    tag="a"
                    href="{{ route('scoring.index') }}"
                    size="sm"
                >
                    Menilai Ujian
                </x-filament::button>
            </x-filament::section>
        @endcan

        <x-filament::section heading="Status Pembimbing dan Penguji">
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <div>
                    <x-filament::button
                        tag="a"
                        href="{{ route('information.guide') }}"
                        size="sm"
                    >
                        Daftar
                    </x-filament::button>
                    <span class="ml-2">Bimbingan saya</span>
                </div>
                <div>
                    <x-filament::button
                        tag="a"
                        href="{{ route('information.pass') }}"
                        size="sm"
                    >
                        Bukti
                    </x-filament::button>
                    <span class="ml-2">Membimbing / Menguji (untuk BKD)</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
