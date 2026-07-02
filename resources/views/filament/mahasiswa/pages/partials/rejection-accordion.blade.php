@if (count($history) > 0)
    <div x-data="{ open: @js($expanded ?? false) }" class="{{ ($expanded ?? false) ? '' : 'mt-3 border-t border-gray-100 pt-3 dark:border-gray-800' }}">
        @if (! ($expanded ?? false))
            <button
                type="button"
                @click="open = ! open"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-danger-600 hover:text-danger-500"
            >
                <x-filament::icon icon="heroicon-m-chevron-down" class="h-4 w-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                <span x-text="open ? 'Sembunyikan histori penolakan' : 'Lihat histori penolakan ({{ count($history) }})'"></span>
            </button>
        @endif

        <div @if (! ($expanded ?? false)) x-show="open" x-collapse @endif class="{{ ($expanded ?? false) ? '' : 'mt-3' }} space-y-2">
            @foreach ($history as $event)
                <div class="rounded-lg border border-danger-200 bg-danger-50/50 p-3 text-sm dark:border-danger-800 dark:bg-danger-950/30">
                    <p class="font-medium text-danger-900 dark:text-danger-100">
                        {{ $event->actorRoleLabel() }} · {{ $event->recorded_at?->format('d M Y H:i') }}
                    </p>
                    <p class="mt-1 text-danger-800 dark:text-danger-200">{{ $event->note }}</p>
                    @if ($event->actor)
                        <p class="mt-1 text-xs text-danger-700">{{ $event->actor->name }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
