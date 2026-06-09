<x-filament-panels::page class="fi-dosen-view-chief-exam-page">
    <x-filament::section heading="Informasi Ujian">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jenis Ujian</dt>
                <dd class="mt-1 text-base font-semibold text-gray-950 dark:text-white">
                    {{ $record->examtype?->name ?? '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mahasiswa</dt>
                <dd class="mt-1 text-base font-semibold text-primary-600 dark:text-primary-400">
                    {{ $record->student?->name ?? '-' }}
                    @if (filled($record->student?->username))
                        <span class="font-normal text-gray-600 dark:text-gray-300">({{ $record->student->username }})</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $record->exam_date?->isoFormat('LL') ?? '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pukul</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $record->exam_time ? \Illuminate\Support\Carbon::parse($record->exam_time)->isoFormat('LT') : '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tempat</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    Ruang {{ $record->room ?? '-' }}
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Judul</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $record->title ?? '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status Kelulusan</dt>
                <dd class="mt-1">
                    @if ($record->pass_exam)
                        <x-filament::badge color="success">Lulus</x-filament::badge>
                    @else
                        <x-filament::badge color="danger">Belum difinalisasi</x-filament::badge>
                    @endif
                </dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section heading="Penilaian Penguji">
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
