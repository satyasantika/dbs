@php
    $statusColor = static fn (string $status): string => match ($status) {
        'accepted' => 'success',
        'rejected' => 'danger',
        'pending'  => 'warning',
        default    => 'gray',
    };
    $statusLabel = static fn (?string $status): string => match ($status) {
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'pending'  => 'Menunggu respons',
        default    => ucfirst((string) $status),
    };
@endphp

@forelse ($proposals as $proposal)
    @php
        $isFirst   = $loop->first;
        $isFinal   = $proposal->final;
        $pCancels  = $cancellations->get($proposal->id, collect());
    @endphp

    <div @class([
        'mb-4 last:mb-0 rounded-xl border p-4',
        'border-success-300 bg-success-50/60 dark:border-success-700 dark:bg-success-950/30' => $isFinal,
        'border-primary-200 bg-primary-50/40 dark:border-primary-700 dark:bg-primary-950/30' => $isFirst && ! $isFinal,
        'border-gray-200 bg-gray-50/60 dark:border-gray-700 dark:bg-gray-900/30' => ! $isFirst && ! $isFinal,
    ])>
        {{-- Proposal header --}}
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Usulan #{{ $proposals->count() - $loop->index }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $proposal->created_at?->translatedFormat('d M Y') }}
            </span>
            @if ($isFinal)
                <x-filament::badge color="success">Ditetapkan</x-filament::badge>
            @elseif ($isFirst)
                <x-filament::badge color="primary">Aktif</x-filament::badge>
            @endif
        </div>

        {{-- Seats --}}
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ([1, 2] as $seat)
                @php
                    $guideName   = $seat === 1 ? $proposal->guide1?->name   : $proposal->guide2?->name;
                    $guideStatus = $seat === 1 ? $proposal->guide1_status    : $proposal->guide2_status;
                    $guideNote   = $seat === 1 ? $proposal->guide1_note      : $proposal->guide2_note;
                    $respondedAt = $seat === 1 ? $proposal->guide1_responded_at : $proposal->guide2_responded_at;
                    $cancel      = $pCancels->firstWhere('subject', 'guide'.$seat);
                @endphp

                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-900/60">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        Calon Pembimbing {{ $seat }}
                    </p>

                    @if ($guideName)
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $guideName }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <x-filament::badge :color="$statusColor($guideStatus)" size="sm">
                                {{ $statusLabel($guideStatus) }}
                            </x-filament::badge>
                            @if ($respondedAt)
                                <span class="text-xs text-gray-400">{{ $respondedAt->translatedFormat('d M Y') }}</span>
                            @endif
                        </div>
                        @if ($guideNote)
                            <p class="mt-1.5 rounded bg-danger-50 px-2 py-1 text-xs text-danger-700 dark:bg-danger-950/50 dark:text-danger-300">
                                {{ $guideNote }}
                            </p>
                        @endif
                    @else
                        <p class="text-sm italic text-gray-400 dark:text-gray-500">Belum diisi</p>
                    @endif

                    @if ($cancel)
                        <div class="mt-2 rounded bg-warning-50 px-2 py-1.5 text-xs dark:bg-warning-950/40">
                            <span class="font-medium text-warning-800 dark:text-warning-300">
                                Dibatalkan {{ $cancel->recorded_at?->translatedFormat('d M Y') }}
                            </span>
                            @if ($cancel->actor?->name)
                                <span class="text-warning-600 dark:text-warning-400"> oleh {{ $cancel->actor->name }}</span>
                            @endif
                            @if (filled($cancel->note))
                                <p class="mt-0.5 text-warning-700 dark:text-warning-300">{{ $cancel->note }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@empty
    <p class="text-sm italic text-gray-500 dark:text-gray-400">Belum ada usulan calon pembimbing.</p>
@endforelse
