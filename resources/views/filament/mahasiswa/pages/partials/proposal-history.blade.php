{{--
  Proposal seat history accordion.
  Props: $history (array), $seat (int)
--}}
<div x-data="{ open: false }" class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
    <button
        type="button"
        x-on:click="open = ! open"
        class="flex w-full items-center gap-1.5 text-left text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
    >
        <x-heroicon-m-clock class="h-3.5 w-3.5 shrink-0" />
        @if (count($history) > 0)
            <span x-text="open ? 'Sembunyikan histori ({{ count($history) }})' : 'Lihat histori ({{ count($history) }})'"></span>
        @else
            <span>Lihat histori</span>
        @endif
        <x-heroicon-m-chevron-down
            class="h-3 w-3 shrink-0 transition-transform duration-200"
            x-bind:class="open ? 'rotate-180' : ''"
        />
    </button>

    <div x-show="open" x-collapse class="mt-3">
        @forelse ($history as $item)
            @php
                $cancelledByLabel = match ($item['actor_role'] ?? null) {
                    \App\Models\NuirRevisionEvent::ROLE_MAHASISWA => 'Dibatalkan Mahasiswa',
                    \App\Models\NuirRevisionEvent::ROLE_MANAJER => 'Dibatalkan Manajer',
                    default => 'Dibatalkan',
                };

                [$icon, $color, $label] = match ($item['type']) {
                    'proposed'  => ['heroicon-s-user-plus',    'text-primary-600 dark:text-primary-400',  'Diusulkan'],
                    'rejected'  => ['heroicon-s-x-circle',     'text-danger-600 dark:text-danger-400',    'Ditolak'],
                    'cancelled' => ['heroicon-s-no-symbol',    'text-warning-600 dark:text-warning-400',  $cancelledByLabel],
                    'accepted'  => ['heroicon-s-check-circle', 'text-success-600 dark:text-success-400',  'Diterima'],
                    default     => ['heroicon-s-information-circle', 'text-gray-400', ucfirst($item['type'])],
                };
            @endphp

            <div class="relative flex gap-3 pb-4 last:pb-0">
                {{-- Vertical connector line --}}
                @if (! $loop->last)
                    <div class="absolute left-[7px] top-5 h-full w-px bg-gray-200 dark:bg-gray-700"></div>
                @endif

                {{-- Icon dot --}}
                <div class="relative mt-0.5 shrink-0">
                    <x-dynamic-component :component="$icon" class="{{ $color }} h-3.5 w-3.5" />
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold {{ $color }}">{{ $label }}</p>

                    @if ($item['guide_name'])
                        <p class="text-xs text-gray-700 dark:text-gray-300">{{ $item['guide_name'] }}</p>
                    @endif

                    @if ($item['actor_name'])
                        <p class="text-xs text-gray-500 dark:text-gray-400">oleh {{ $item['actor_name'] }}</p>
                    @endif

                    @if (filled($item['note'] ?? null))
                        <p class="mt-0.5 text-xs italic text-gray-500 dark:text-gray-400">"{{ $item['note'] }}"</p>
                    @endif

                    <p class="mt-0.5 text-[10px] text-gray-400 dark:text-gray-500">
                        {{ $item['at']?->translatedFormat('d M Y, H:i') ?? '—' }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-xs text-gray-400 dark:text-gray-500">Belum ada histori untuk kursi ini.</p>
        @endforelse
    </div>
</div>
