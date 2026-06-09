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
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Nilai</dt>
            <dd class="text-right">
                <x-filament::badge color="primary">
                    {{ filled($record->letter) ? $record->letter : number_format((float) $record->grade, 2) }}
                </x-filament::badge>
            </dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Lulus</dt>
            <dd class="text-right">
                @if ($record->pass_approved)
                    <x-filament::badge color="success" icon="heroicon-m-check-circle">
                        Lulus
                    </x-filament::badge>
                @else
                    <x-filament::badge color="danger" icon="heroicon-m-x-circle">
                        Tidak lulus
                    </x-filament::badge>
                @endif
            </dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Revisi</dt>
            <dd class="text-right">
                @if ($record->revision)
                    <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle">
                        Revisi
                    </x-filament::badge>
                @else
                    <x-filament::badge color="success" icon="heroicon-m-check">
                        Tidak ada
                    </x-filament::badge>
                @endif
            </dd>
        </div>

        @if ($record->revision && filled($record->revision_note))
            <div class="flex items-start justify-between gap-3">
                <dt class="shrink-0 text-gray-500 dark:text-gray-400">Catatan</dt>
                <dd class="min-w-0 break-words text-right font-medium text-gray-950 dark:text-white">
                    {{ \Illuminate\Support\Str::limit($record->revision_note, 80) }}
                </dd>
            </div>
        @endif
    </dl>

    @include('filament.dosen.pages.partials.scoring-examiners', ['registration' => $registration])
</div>
