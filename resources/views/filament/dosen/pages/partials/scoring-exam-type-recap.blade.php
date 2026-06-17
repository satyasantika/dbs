@php
    use App\Services\Examination\DosenScoringPresenter;

    $recapTypes = $this->getExamTypeRecap();
    $recapTotal = DosenScoringPresenter::examTypeRecapTotal($recapTypes);
@endphp

<x-filament::section>
    <x-slot name="heading">
        Rekap Jenis Ujian
    </x-slot>

    <x-slot name="description">
        Dosen penilai:
        <span class="font-semibold text-gray-950 dark:text-white">{{ auth()->user()->name }}</span>
    </x-slot>

    <style>
        .scoring-exam-type-recap-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 640px) {
            .scoring-exam-type-recap-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .scoring-exam-type-recap-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
    </style>

    <div class="scoring-exam-type-recap-grid">
        @foreach ($recapTypes as $type)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <x-filament::badge :color="$type['color']">
                    {{ $type['name'] }}
                </x-filament::badge>

                <div class="mt-3 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">
                    {{ $type['count'] }}
                </div>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    penilaian
                </p>
            </div>
        @endforeach

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/10">
            <x-filament::badge color="gray">
                Total
            </x-filament::badge>

            <div class="mt-3 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">
                {{ $recapTotal }}
            </div>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                seluruh jenis ujian
            </p>
        </div>
    </div>
</x-filament::section>
