@php
    /** @var \App\Models\GuideExaminer $record */
    $record = $getRecord();
    $studentName = $record->student?->name ?? '-';
    $npm = $record->student?->username ?? '—';
    $roleLabel = \App\Services\Information\AcademicSemester::roleLabel($record, auth()->id());
    $studyDuration = \App\Services\Information\AcademicSemester::studyDuration($record);
@endphp

<div class="flex h-full min-w-0 flex-col gap-4">
    <div class="min-w-0 space-y-2">
        <h3 class="break-words text-base font-semibold leading-snug text-gray-950 dark:text-white">
            {{ $studentName }}
        </h3>

        <div class="flex flex-wrap gap-2">
            <x-filament::badge :color="\App\Services\Information\AcademicSemester::roleBadgeColor($record, auth()->id())">
                {{ $roleLabel }}
            </x-filament::badge>

            <x-filament::badge color="gray">
                Angkatan {{ $record->year_generation }}
            </x-filament::badge>
        </div>
    </div>

    <dl class="grid flex-1 gap-2.5 text-sm">
        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">NPM</dt>
            <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $npm }}</dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Sidang</dt>
            <dd class="text-right font-medium text-gray-950 dark:text-white">
                {{ $record->thesis_date?->isoFormat('D MMM Y') ?? '—' }}
            </dd>
        </div>

        @if (filled($studyDuration))
            <div class="flex items-start justify-between gap-3">
                <dt class="shrink-0 text-gray-500 dark:text-gray-400">Masa studi</dt>
                <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $studyDuration }}</dd>
            </div>
        @endif
    </dl>
</div>
