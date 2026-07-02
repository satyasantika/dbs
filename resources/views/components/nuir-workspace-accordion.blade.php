@props([
    'defaultOpen'  => false,
    'embedded'     => false,
    'color'        => 'gray',
    'collapsible'  => true,
])

@php
    $borderClass = match ($color) {
        'success' => 'border-success-400 dark:border-success-600',
        'warning' => 'border-warning-400 dark:border-warning-500',
        'danger'  => 'border-danger-400 dark:border-danger-600',
        'info'    => 'border-info-400 dark:border-info-600',
        'primary' => 'border-primary-400 dark:border-primary-600',
        default   => 'border-gray-200 dark:border-gray-700',
    };

    $headerBgClass = match ($color) {
        'success' => 'hover:bg-success-50 dark:hover:bg-success-950/30',
        'warning' => 'hover:bg-warning-50 dark:hover:bg-warning-950/30',
        'danger'  => 'hover:bg-danger-50 dark:hover:bg-danger-950/30',
        'info'    => 'hover:bg-info-50 dark:hover:bg-info-950/30',
        'primary' => 'hover:bg-primary-50 dark:hover:bg-primary-950/30',
        default   => 'hover:bg-gray-50 dark:hover:bg-gray-900/40',
    };

    $wrapperClasses = 'overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950/40 '.$borderClass;
@endphp

@if ($embedded)
    <button
        type="button"
        @click="accordionOpen = ! accordionOpen"
        class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left transition {{ $headerBgClass }}"
    >
        <div class="min-w-0 flex-1">
            {{ $header }}
        </div>
        <x-filament::icon
            icon="heroicon-m-chevron-down"
            class="mt-0.5 h-5 w-5 shrink-0 text-gray-500 transition-transform dark:text-gray-400"
            x-bind:class="accordionOpen ? 'rotate-180' : ''"
        />
    </button>

    <div
        x-show="accordionOpen"
        x-collapse
        class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800"
    >
        {{ $slot }}
    </div>
@elseif ($collapsible)
    <div
        {{ $attributes->class([$wrapperClasses]) }}
        x-data="{ accordionOpen: @js($defaultOpen) }"
    >
        <button
            type="button"
            @click="accordionOpen = ! accordionOpen; if (accordionOpen) $nextTick(() => $dispatch('nuir-accordion-opened', { el: $root }))"
            class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left transition {{ $headerBgClass }}"
        >
            <div class="min-w-0 flex-1">
                {{ $header }}
            </div>
            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="mt-0.5 h-5 w-5 shrink-0 text-gray-500 transition-transform dark:text-gray-400"
                x-bind:class="accordionOpen ? 'rotate-180' : ''"
            />
        </button>

        <div
            x-show="accordionOpen"
            x-collapse
            class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800"
        >
            {{ $slot }}
        </div>
    </div>
@else
    {{-- non-collapsible: static card with colored border --}}
    <div {{ $attributes->class([$wrapperClasses]) }}>
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            {{ $header }}
        </div>
        <div class="px-4 pb-4 pt-3">
            {{ $slot }}
        </div>
    </div>
@endif
