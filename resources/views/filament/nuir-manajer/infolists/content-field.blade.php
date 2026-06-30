@php
    $accentClasses = match ($accent) {
        'primary' => [
            'border' => 'border-primary-300 dark:border-primary-700',
            'bg' => 'bg-primary-50/80 dark:bg-primary-950/40',
            'badge' => 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300',
            'icon' => 'text-primary-600 dark:text-primary-400',
            'title' => 'text-primary-950 dark:text-primary-100',
        ],
        'info' => [
            'border' => 'border-info-300 dark:border-info-700',
            'bg' => 'bg-info-50/80 dark:bg-info-950/40',
            'badge' => 'bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200',
            'icon' => 'text-info-600 dark:text-info-400',
            'title' => 'text-info-950 dark:text-info-100',
        ],
        'warning' => [
            'border' => 'border-warning-300 dark:border-warning-700',
            'bg' => 'bg-warning-50/80 dark:bg-warning-950/40',
            'badge' => 'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200',
            'icon' => 'text-warning-600 dark:text-warning-400',
            'title' => 'text-warning-950 dark:text-warning-100',
        ],
        'success' => [
            'border' => 'border-success-300 dark:border-success-700',
            'bg' => 'bg-success-50/80 dark:bg-success-950/40',
            'badge' => 'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200',
            'icon' => 'text-success-600 dark:text-success-400',
            'title' => 'text-success-950 dark:text-success-100',
        ],
        default => [
            'border' => 'border-gray-300 dark:border-gray-700',
            'bg' => 'bg-gray-50 dark:bg-gray-900/40',
            'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            'icon' => 'text-gray-600 dark:text-gray-400',
            'title' => 'text-gray-950 dark:text-gray-100',
        ],
    };

    $isTitle = $field === 'title';
@endphp

<div @class([
    'rounded-xl border p-4 shadow-sm',
    $accentClasses['border'],
    $accentClasses['bg'],
    'mb-4 last:mb-0' => ! $isTitle,
])>
    <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
        <div class="flex min-w-0 items-start gap-3">
            <div @class([
                'flex shrink-0 items-center justify-center rounded-lg',
                $isTitle ? 'h-10 w-10' : 'h-9 w-9',
                $accentClasses['badge'],
            ])>
                @if ($badge)
                    <span class="text-sm font-bold">{{ $badge }}</span>
                @else
                    <x-filament::icon :icon="$icon" @class(['h-5 w-5', $accentClasses['icon']]) />
                @endif
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($badge)
                        <x-filament::icon :icon="$icon" @class(['h-4 w-4', $accentClasses['icon']]) />
                    @endif
                    <h3 @class([
                        'font-semibold leading-tight',
                        $isTitle ? 'text-lg' : 'text-base',
                        $accentClasses['title'],
                    ])>
                        {{ $label }}
                    </h3>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            </div>
        </div>

        <span @class([
            'inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-medium',
            $accentClasses['badge'],
        ])>
            {{ $wordMeta }}
        </span>
    </div>

    <div @class([
        'rounded-lg border border-white/60 bg-white/70 p-4 dark:border-white/10 dark:bg-gray-950/30',
        $isEmpty ? 'italic text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200',
        $isTitle ? 'text-base font-medium leading-relaxed' : 'text-sm leading-relaxed whitespace-pre-wrap',
    ])>
        {{ $content }}
    </div>
</div>
