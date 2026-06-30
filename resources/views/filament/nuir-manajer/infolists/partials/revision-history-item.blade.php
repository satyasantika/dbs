@php
    $toneClasses = match ($item['tone'] ?? 'gray') {
        'primary' => [
            'border' => 'border-primary-300 dark:border-primary-700',
            'bg' => 'bg-primary-50/90 dark:bg-primary-950/50',
            'header' => 'hover:bg-primary-100/80 dark:hover:bg-primary-900/40',
            'bodyBorder' => 'border-primary-200 dark:border-primary-800',
            'note' => 'bg-primary-100 text-primary-900 dark:bg-primary-900/60 dark:text-primary-100',
            'content' => 'bg-white/80 text-primary-950 dark:bg-primary-950/30 dark:text-primary-100',
            'badge' => 'bg-primary-200 text-primary-900 dark:bg-primary-800 dark:text-primary-100',
        ],
        'info' => [
            'border' => 'border-info-300 dark:border-info-700',
            'bg' => 'bg-info-50/90 dark:bg-info-950/50',
            'header' => 'hover:bg-info-100/80 dark:hover:bg-info-900/40',
            'bodyBorder' => 'border-info-200 dark:border-info-800',
            'note' => 'bg-info-100 text-info-900 dark:bg-info-900/60 dark:text-info-100',
            'content' => 'bg-white/80 text-info-950 dark:bg-info-950/30 dark:text-info-100',
            'badge' => 'bg-info-200 text-info-900 dark:bg-info-800 dark:text-info-100',
        ],
        'warning' => [
            'border' => 'border-warning-300 dark:border-warning-700',
            'bg' => 'bg-warning-50/90 dark:bg-warning-950/50',
            'header' => 'hover:bg-warning-100/80 dark:hover:bg-warning-900/40',
            'bodyBorder' => 'border-warning-200 dark:border-warning-800',
            'note' => 'bg-warning-100 text-warning-900 dark:bg-warning-900/60 dark:text-warning-100',
            'content' => 'bg-white/80 text-warning-950 dark:bg-warning-950/30 dark:text-warning-100',
            'badge' => 'bg-warning-200 text-warning-900 dark:bg-warning-800 dark:text-warning-100',
        ],
        'success' => [
            'border' => 'border-success-300 dark:border-success-700',
            'bg' => 'bg-success-50/90 dark:bg-success-950/50',
            'header' => 'hover:bg-success-100/80 dark:hover:bg-success-900/40',
            'bodyBorder' => 'border-success-200 dark:border-success-800',
            'note' => 'bg-success-100 text-success-900 dark:bg-success-900/60 dark:text-success-100',
            'content' => 'bg-white/80 text-success-950 dark:bg-success-950/30 dark:text-success-100',
            'badge' => 'bg-success-200 text-success-900 dark:bg-success-800 dark:text-success-100',
        ],
        'danger' => [
            'border' => 'border-danger-300 dark:border-danger-700',
            'bg' => 'bg-danger-50/90 dark:bg-danger-950/50',
            'header' => 'hover:bg-danger-100/80 dark:hover:bg-danger-900/40',
            'bodyBorder' => 'border-danger-200 dark:border-danger-800',
            'note' => 'bg-danger-100 text-danger-900 dark:bg-danger-900/60 dark:text-danger-100',
            'content' => 'bg-white/80 text-danger-950 dark:bg-danger-950/30 dark:text-danger-100',
            'badge' => 'bg-danger-200 text-danger-900 dark:bg-danger-800 dark:text-danger-100',
        ],
        default => [
            'border' => 'border-gray-300 dark:border-gray-700',
            'bg' => 'bg-gray-50/90 dark:bg-gray-950/50',
            'header' => 'hover:bg-gray-100/80 dark:hover:bg-gray-900/40',
            'bodyBorder' => 'border-gray-200 dark:border-gray-800',
            'note' => 'bg-gray-100 text-gray-900 dark:bg-gray-900/60 dark:text-gray-100',
            'content' => 'bg-white/80 text-gray-800 dark:bg-gray-950/30 dark:text-gray-200',
            'badge' => 'bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        ],
    };
@endphp

<div
    x-data="{ itemOpen: {{ ($index ?? 0) === 0 ? 'true' : 'false' }} }"
    @class([
        'overflow-hidden rounded-lg border shadow-sm',
        $toneClasses['border'],
        $toneClasses['bg'],
    ])
>
    <button
        type="button"
        @click="itemOpen = ! itemOpen"
        @class([
            'flex w-full items-start justify-between gap-3 px-3 py-2.5 text-left',
            $toneClasses['header'],
        ])
    >
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['heading'] }}</p>
                @if (! empty($item['submission_version']))
                    <span @class([
                        'inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                        $toneClasses['badge'],
                    ])>
                        v{{ $item['submission_version'] }}
                    </span>
                @endif
            </div>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">
                @if ($item['recorded_at'])
                    {{ $item['recorded_at']->format('d M Y H:i') }}
                @endif
                @if ($item['actor_name'])
                    · {{ $item['actor_name'] }}
                @endif
            </p>
        </div>
        <x-filament::icon
            icon="heroicon-m-chevron-down"
            class="mt-0.5 h-4 w-4 shrink-0 text-gray-500 transition-transform dark:text-gray-400"
            x-bind:class="itemOpen ? 'rotate-180' : ''"
        />
    </button>

    <div
        x-show="itemOpen"
        x-collapse
        @class(['border-t px-3 py-2.5 text-sm', $toneClasses['bodyBorder']])
    >
        @if (filled($item['note']))
            <p @class(['mb-2 rounded-md px-2.5 py-2', $toneClasses['note']])>
                <span class="font-medium">Catatan:</span> {{ $item['note'] }}
            </p>
        @endif

        @if (filled($item['content']))
            <div @class(['rounded-md px-2.5 py-2 whitespace-pre-wrap', $toneClasses['content']])>
                {{ $item['content'] }}
            </div>
        @elseif (blank($item['note']))
            <p class="text-xs italic text-gray-500 dark:text-gray-400">Tidak ada detail tambahan.</p>
        @endif
    </div>
</div>
