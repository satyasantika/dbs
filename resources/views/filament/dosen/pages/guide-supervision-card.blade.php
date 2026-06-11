@php
    /** @var \App\Models\GuideExaminer $record */
    $record = $getRecord();
    $studentName = $record->student?->name ?? '-';
    $npm = $record->student?->username ?? '—';
    $progressLabel = \App\Filament\Dosen\Pages\GuideSupervision::progressLabel($record);
    $roles = collect([
        auth()->id() === (int) $record->guide1_id ? 'P1' : null,
        auth()->id() === (int) $record->guide2_id ? 'P2' : null,
    ])->filter()->values();

    $formatDate = fn ($date) => filled($date)
        ? \Illuminate\Support\Carbon::parse($date)->isoFormat('D MMM Y')
        : '—';
@endphp

<div class="flex h-full min-w-0 flex-col gap-4">
    <div class="min-w-0 space-y-2">
        <h3 class="break-words text-base font-semibold leading-snug text-gray-950 dark:text-white">
            {{ $studentName }}
        </h3>

        <div class="flex flex-wrap gap-2">
            <x-filament::badge color="gray">
                Angkatan {{ $record->year_generation }}
            </x-filament::badge>

            <x-filament::badge :color="\App\Filament\Dosen\Pages\GuideSupervision::progressBadgeColor($record)">
                {{ $progressLabel }}
            </x-filament::badge>

            @foreach ($roles as $role)
                <x-filament::badge :color="\App\Filament\Dosen\Pages\GuideSupervision::roleBadgeColor($role)">
                    {{ $role }}
                </x-filament::badge>
            @endforeach
        </div>
    </div>

    <dl class="grid flex-1 gap-2.5 text-sm">
        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">NPM</dt>
            <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $npm }}</dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Pembimbing 1</dt>
            <dd class="min-w-0 break-words text-right font-medium text-gray-950 dark:text-white">
                {{ $record->guide1?->name ?? '—' }}
            </dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Pembimbing 2</dt>
            <dd class="min-w-0 break-words text-right font-medium text-gray-950 dark:text-white">
                {{ $record->guide2?->name ?? '—' }}
            </dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Sempro</dt>
            <dd class="text-right font-medium text-gray-950 dark:text-white">
                {{ $formatDate($record->proposal_date) }}
            </dd>
        </div>

        <div class="flex items-start justify-between gap-3">
            <dt class="shrink-0 text-gray-500 dark:text-gray-400">Semhas</dt>
            <dd class="text-right font-medium text-gray-950 dark:text-white">
                {{ $formatDate($record->seminar_date) }}
            </dd>
        </div>
    </dl>
</div>
