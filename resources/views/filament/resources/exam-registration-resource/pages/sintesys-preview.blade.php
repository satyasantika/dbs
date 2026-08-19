<div class="space-y-3">
    @php
        $preview = is_array($getState() ?? null) ? $getState() : [];
        $rows = $preview['rows'] ?? [];
        $total = (int) ($preview['total'] ?? 0);
        $baru = (int) ($preview['baru'] ?? 0);
        $duplikat = (int) ($preview['duplikat'] ?? 0);
        $error = (int) ($preview['error'] ?? 0);
        $errorMessage = $preview['error_message'] ?? null;
    @endphp

    @if (filled($errorMessage))
        <p class="text-sm text-danger-600 dark:text-danger-400">{{ $errorMessage }}</p>
    @elseif ($preview === [])
        <p class="text-sm text-gray-600 dark:text-gray-300">Mengambil data dari Sintesys…</p>
    @elseif ($total === 0)
        <p class="text-sm text-gray-600 dark:text-gray-300">Tidak ada data ujian pada rentang tanggal ini.</p>
    @else
        <div class="flex flex-wrap gap-2">
            <x-filament::badge color="info" size="sm">Total {{ $total }}</x-filament::badge>
            <x-filament::badge color="success" size="sm">Baru {{ $baru }}</x-filament::badge>
            <x-filament::badge color="warning" size="sm">Duplikat {{ $duplikat }}</x-filament::badge>
            <x-filament::badge color="danger" size="sm">Error {{ $error }}</x-filament::badge>
        </div>

        <div class="grid max-h-96 grid-cols-1 gap-2 overflow-auto sm:grid-cols-2">
            @foreach ($rows as $row)
                @php
                    $status = $row['status'] ?? 'error';
                    $statusColor = match ($status) {
                        'baru' => 'success',
                        'duplikat' => 'warning',
                        default => 'danger',
                    };
                    $statusLabel = match ($status) {
                        'baru' => 'Baru',
                        'duplikat' => 'Duplikat',
                        default => 'Error',
                    };
                    $jenisColor = $row['jenis_ujian_color'] ?? 'gray';
                    $penguji = is_array($row['penguji'] ?? null) ? $row['penguji'] : [];
                @endphp
                <article class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $row['nama'] ?? '—' }}</div>
                            <div class="font-mono text-xs text-gray-500">{{ $row['nim'] ?? '—' }}</div>
                        </div>
                        <x-filament::badge :color="$statusColor" size="sm">{{ $statusLabel }}</x-filament::badge>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <x-filament::badge :color="$jenisColor" size="sm">{{ $row['jenis_ujian'] ?? '—' }}</x-filament::badge>
                        <span class="text-xs text-gray-600 dark:text-gray-300">{{ $row['exam_date_label'] ?? $row['exam_date'] ?? '—' }}</span>
                    </div>

                    @if ($penguji !== [])
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-700 dark:text-gray-300">
                            @foreach ($penguji as $urutan => $namaPenguji)
                                <span>{{ $urutan }}. {{ $namaPenguji }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if ($status === 'error' && filled($row['message'] ?? null))
                        <p class="mt-2 text-xs text-danger-600 dark:text-danger-400">{{ $row['message'] }}</p>
                    @endif

                    @foreach ($row['warnings'] ?? [] as $warning)
                        <p class="mt-1 text-xs text-warning-700 dark:text-warning-300">{{ $warning }}</p>
                    @endforeach
                </article>
            @endforeach
        </div>
    @endif
</div>
