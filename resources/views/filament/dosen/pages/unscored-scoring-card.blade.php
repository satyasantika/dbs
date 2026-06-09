@php
    /** @var \App\Models\ExamScore $record */
    $record = $getRecord();
    $registration = $record->registration;
    $examType = $registration?->examtype;
    $studentName = $registration?->student?->name ?? '-';
    $examTypeName = $examType?->name ?? '-';
@endphp

<div class="flex h-full min-w-0 flex-col gap-4">
    <div class="min-w-0 space-y-2">
        <h3 class="break-words text-base font-semibold leading-snug text-gray-950 dark:text-white">
            {{ $studentName }}
        </h3>

        <x-filament::badge :color="\App\Filament\Dosen\Pages\UnscoredScoring::examTypeBadgeColor($examTypeName, $examType?->code)">
            {{ $examTypeName }}
        </x-filament::badge>
    </div>

    <dl class="grid flex-1 gap-2.5 text-sm">
        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Tanggal</dt>
            <dd class="text-right font-medium text-gray-950 dark:text-white">
                {{ $registration?->exam_date?->format('d M Y') ?? '—' }}
            </dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Waktu</dt>
            <dd class="text-right font-medium text-gray-950 dark:text-white">
                {{ $registration?->exam_time ? \Illuminate\Support\Carbon::parse($registration->exam_time)->format('H:i') : '—' }}
            </dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Nilai Anda</dt>
            <dd class="text-right">
                @if (filled($record->grade))
                    <x-filament::badge color="primary">
                        {{ filled($record->letter) ? $record->letter : number_format((float) $record->grade, 2) }}
                    </x-filament::badge>
                @else
                    <x-filament::badge color="danger">
                        Belum dinilai
                    </x-filament::badge>
                @endif
            </dd>
        </div>
    </dl>

    @include('filament.dosen.pages.partials.scoring-examiners', ['registration' => $registration])
</div>
