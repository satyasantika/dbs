<x-filament-panels::page>
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

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-2 py-1 text-left">Dosen 1</th>
                            <th class="px-2 py-1 text-left">Status 1</th>
                            <th class="px-2 py-1 text-left">Catatan 1</th>
                            <th class="px-2 py-1 text-left">Dosen 2</th>
                            <th class="px-2 py-1 text-left">Status 2</th>
                            <th class="px-2 py-1 text-left">Catatan 2</th>
                            <th class="px-2 py-1 text-left">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->proposals as $proposal)
                            <tr class="border-b">
                                <td class="px-2 py-1">{{ $proposal->guide1?->name ?? '—' }}</td>
                                <td class="px-2 py-1"><x-filament::badge color="gray">{{ $proposal->guide1_status }}</x-filament::badge></td>
                                <td class="px-2 py-1">{{ $proposal->guide1_note }}</td>
                                <td class="px-2 py-1">{{ $proposal->guide2?->name ?? '—' }}</td>
                                <td class="px-2 py-1"><x-filament::badge color="gray">{{ $proposal->guide2_status }}</x-filament::badge></td>
                                <td class="px-2 py-1">{{ $proposal->guide2_note }}</td>
                                <td class="px-2 py-1">{{ $proposal->created_at?->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-2 py-3 text-gray-500">Belum ada usulan calon pembimbing.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
