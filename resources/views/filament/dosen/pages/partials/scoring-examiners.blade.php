@php
    use App\Services\Examination\DosenScoringPresenter;

    /** @var \App\Models\ExamRegistration|null $registration */
    $examiners = $registration
        ? DosenScoringPresenter::examinersForRegistration($registration, auth()->id())
        : [];
@endphp

@once
    <style>
        .fi-ta-record .scoring-examiner-name--scored {
            color: #16a34a !important;
            font-weight: 500;
        }

        .fi-ta-record .scoring-examiner-name--pending {
            color: #dc2626 !important;
            font-weight: 500;
        }

        .dark .fi-ta-record .scoring-examiner-name--scored {
            color: #4ade80 !important;
        }

        .dark .fi-ta-record .scoring-examiner-name--pending {
            color: #f87171 !important;
        }
    </style>
@endonce

@if ($examiners !== [])
    <div class="scoring-examiners border-t border-gray-200 pt-3 dark:border-white/10">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Dosen Penguji
        </p>

        <ul class="mt-1.5 space-y-1">
            @foreach ($examiners as $examiner)
                <li class="text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                    {{ $examiner['order'] }}.
                    @if ($examiner['is_chief'])
                        ★
                    @endif

                    <span @class([
                        'scoring-examiner-name',
                        'scoring-examiner-name--scored' => $examiner['is_scored'],
                        'scoring-examiner-name--pending' => ! $examiner['is_scored'],
                    ])>
                        {{ $examiner['name'] }}
                    </span>

                    ({{ $examiner['is_scored'] ? 'Sudah' : 'Belum' }})

                    @if ($examiner['is_current'])
                        <span class="text-primary-600 dark:text-primary-400">(Anda)</span>
                    @endif

                    @if ($examiner['is_chief'])
                        <span class="text-gray-500 dark:text-gray-400">· Ketua</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
