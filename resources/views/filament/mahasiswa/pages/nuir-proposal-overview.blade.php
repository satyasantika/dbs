<x-filament-panels::page>
    <div wire:poll.15s.visible="pollProposals"></div>

    <div class="space-y-4">
        @if (session('success'))
            <x-filament::section>
                <p class="text-success-600">{{ session('success') }}</p>
            </x-filament::section>
        @endif

        @if (session('warning'))
            <x-filament::section>
                <p class="text-warning-600">{{ session('warning') }}</p>
            </x-filament::section>
        @endif

        @if ($this->finalProposal)
            <div class="rounded-lg border border-success-300 bg-success-50 p-3 text-sm text-success-800">
                Pembimbing sudah ditetapkan: {{ $this->finalProposal->guide1->name }} &amp; {{ $this->finalProposal->guide2->name }}
            </div>
        @endif

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>Usulan Calon Pembimbing NUIR</span>
                    @if (!$this->finalProposal && $this->proposableSubmission)
                        @can('create nuir proposal')
                            <x-filament::button
                                tag="a"
                                href="{{ \App\Filament\Mahasiswa\Pages\CreateNuirProposal::getUrl(panel: 'mahasiswa') }}"
                                size="sm"
                            >
                                Buat Usulan
                            </x-filament::button>
                        @endcan
                    @endif
                </div>
            </x-slot>

            {{-- Card grid, bukan <table> — mengikuti pola card yang sama dengan
                 resource lain (contentGrid + card Filament asli), ditulis manual
                 di sini karena halaman ini bukan komponen Table Filament
                 (Livewire biasa dengan properti $proposals), jadi tidak bisa
                 pakai ->contentGrid()/Layout\Stack. Kelas fi-ta-record & fi-ta-
                 content-grid sama persis dengan yang dipakai Filament sendiri
                 (vendor/filament/tables/resources/views/index.blade.php) supaya
                 tampilannya identik dengan card resource lain. --}}
            <div class="fi-ta-content-grid gap-4 p-4 sm:px-6">
                @forelse ($this->proposals as $proposal)
                    <div class="fi-ta-record relative h-full rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 transition duration-75 dark:bg-white/5 dark:ring-white/10">
                        <div class="fi-ta-col-wrp flex flex-col gap-y-2 px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $proposal->guide1?->name ?? '—' }}</span>
                                <x-filament::badge color="gray">{{ $proposal->guide1_status }}</x-filament::badge>
                            </div>
                            @if ($proposal->guide1_note)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $proposal->guide1_note }}</p>
                            @endif

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $proposal->guide2?->name ?? '—' }}</span>
                                <x-filament::badge color="gray">{{ $proposal->guide2_status }}</x-filament::badge>
                            </div>
                            @if ($proposal->guide2_note)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $proposal->guide2_note }}</p>
                            @endif

                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <x-nuir.human-date :date="$proposal->created_at" />
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full px-2 py-3 text-gray-500">Belum ada usulan calon pembimbing.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
