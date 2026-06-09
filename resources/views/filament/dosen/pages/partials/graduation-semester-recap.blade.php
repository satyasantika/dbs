@php
    $rekap = $this->getGraduationRecapBySemester();
@endphp

<x-filament::section>
    <x-slot name="heading">
        Rekap Kelulusan per Semester
    </x-slot>

    @if (empty($rekap))
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Belum ada data kelulusan.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[36rem] text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th scope="col" class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Semester</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Pembimbing</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Penguji</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($rekap as $row)
                        <tr>
                            <th scope="row" class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white">
                                {{ $row['label'] }}
                            </th>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['pembimbing'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $row['penguji'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-semibold text-gray-950 dark:text-white">
                                {{ $row['pembimbing'] + $row['penguji'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::section>
