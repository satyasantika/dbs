@php
    $invalid = $invalid ?? false;
    $colSpan = $colSpan ?? '';
    $normalized = (! $invalid && filled($value)) ? \App\Support\NuirExternalUrl::normalize($value) : null;
@endphp

<div class="rounded-lg border p-3 {{ $colSpan }} {{ $invalid
    ? 'border-danger-300 bg-danger-50 dark:border-danger-700 dark:bg-danger-950/40'
    : 'border-gray-100 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-900/50' }}">
    <p class="mb-1 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide {{ $invalid ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500' }}">
        {{ $label }}
        @if ($invalid)
            <span class="normal-case font-semibold">&middot; tidak valid</span>
        @endif
    </p>
    @if ($normalized)
        <a href="{{ $normalized }}" target="_blank" rel="noopener noreferrer" class="break-all text-teal-700 hover:underline dark:text-teal-300">
            {{ $value }}
        </a>
    @else
        <p class="break-all {{ $invalid ? 'text-danger-700 dark:text-danger-300' : 'text-gray-700 dark:text-gray-300' }}">{{ $value }}</p>
    @endif
</div>
