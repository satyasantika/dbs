@php
    $rekap = $this->getGraduationRecapBySemester();
    $visibleRows = array_slice($rekap, 0, 2);
    $hiddenRows = array_slice($rekap, 2);
    $hiddenCount = count($hiddenRows);
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
        <div x-data="{ showMore: false }">
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
                        @foreach ($visibleRows as $row)
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

                    @if ($hiddenCount > 0)
                        <tbody
                            x-show="showMore"
                            x-cloak
                            class="divide-y divide-gray-200 dark:divide-white/10"
                        >
                            @foreach ($hiddenRows as $row)
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
                    @endif
                </table>
            </div>

            @if ($hiddenCount > 0)
                <div class="mt-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
                        x-on:click="showMore = ! showMore"
                    >
                        <span x-show="! showMore">
                            Lihat {{ $hiddenCount }} semester lainnya
                        </span>
                        <span x-show="showMore" x-cloak>
                            Tutup semester lainnya
                        </span>
                        <x-filament::icon
                            icon="heroicon-m-chevron-down"
                            class="h-4 w-4 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': showMore }"
                        />
                    </button>
                </div>
            @endif
        </div>
    @endif
</x-filament::section>
