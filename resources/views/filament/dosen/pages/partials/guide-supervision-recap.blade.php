@php
    $rekap = $this->getGuideRecapByGeneration();
@endphp

<x-filament::section>
    <x-slot name="heading">
        Rekap Bimbingan per Angkatan
    </x-slot>

    <x-slot name="description">
        Per {{ now()->isoFormat('D MMMM Y') }} · hanya mahasiswa yang belum sidang skripsi
    </x-slot>

    @if (empty($rekap))
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Belum ada data bimbingan.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th scope="col" class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Thn</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">P1</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">P2</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Belum Sempro</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Baru Sempro</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Sudah Semhas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($rekap as $row)
                        <tr>
                            <th scope="row" class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white">{{ $row['angkatan'] }}</th>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['total'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['pembimbing1'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['pembimbing2'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['belum_sempro'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['baru_sempro'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['sudah_semhas'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::section>
