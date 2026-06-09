@php
    $previousExams = $previousExams ?? [];
    $registration = $scoring->registration;
    $examType = $registration?->examtype;
    $examTypeName = $examType?->name ?? '-';
    $formDisabled = $form_disabled ?? $available_check ?? false;
    $saveButtonLabel = $save_button_label ?? 'Simpan Penilaian';
@endphp

@include('filament.dosen.pages.partials.scoring-form-styles')

<div class="space-y-6">
    <x-filament::section heading="Informasi Mahasiswa">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <x-filament::badge :color="\App\Filament\Dosen\Pages\UnscoredScoring::examTypeBadgeColor($examTypeName, $examType?->code)">
                {{ $examTypeName }}
            </x-filament::badge>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mahasiswa</dt>
                <dd class="mt-1 text-base font-semibold text-primary-600 dark:text-primary-400">
                    {{ $registration?->student?->name ?? '-' }}
                    @if (filled($registration?->student?->username))
                        <span class="font-normal text-gray-600 dark:text-gray-300">({{ $registration->student->username }})</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Penilai</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $scoring->lecture?->name ?? '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Ujian</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $registration?->exam_date?->format('d M Y') ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Waktu</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $registration?->exam_time ? \Illuminate\Support\Carbon::parse($registration->exam_time)->format('H:i') : '—' }}
                </dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Judul</dt>
                <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $registration?->title ?? '—' }}
                </dd>
            </div>
        </dl>

        <div class="mt-4 flex flex-wrap gap-2">
            @if ($registration?->exam_file)
                <x-filament::button
                    tag="a"
                    href="{{ $registration->exam_file }}"
                    target="_blank"
                    icon="heroicon-m-arrow-top-right-on-square"
                    color="success"
                    size="sm"
                >
                    File ujian
                </x-filament::button>
            @else
                <x-filament::badge color="danger">
                    File ujian belum ada
                </x-filament::badge>
            @endif

            @if (count($previousExams) > 0)
                <x-filament::button
                    type="button"
                    color="gray"
                    size="sm"
                    icon="heroicon-m-clock"
                    onclick="document.getElementById('previousExamsModal')?.showModal()"
                >
                    Riwayat penilaian
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>

    @if ($exam_not_started_yet)
        <x-filament::section>
            <div class="py-8 text-center text-lg font-semibold text-danger-600 dark:text-danger-400">
                Ujian belum dimulai
            </div>
        </x-filament::section>
    @else
        @if ($errors->has('revision_note'))
            <div class="rounded-lg border border-danger-300 bg-danger-50 px-4 py-3 text-sm font-semibold text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-400">
                {{ $errors->first('revision_note') }}
            </div>
        @endif

        <x-filament::section
            heading="Form Penilaian"
            description="Pilih metode Nilai Huruf atau Nilai Per Aspek. Keterangan metode aktif akan berubah sesuai tombol yang dipilih."
        >
            @include('examination.partials.scoring-form-body', ['for_filament_panel' => true])
        </x-filament::section>

        @include('examination.partials.scoring-form-script')
    @endif
</div>

@if (count($previousExams) > 0)
    <dialog id="previousExamsModal" class="dbs-history-dialog">
        <div class="dbs-history-dialog-header">
            <h2 class="dbs-history-dialog-title">Riwayat Penilaian Ujian Mahasiswa</h2>
            <button type="button" class="dbs-history-dialog-close" onclick="document.getElementById('previousExamsModal')?.close()" aria-label="Tutup">×</button>
        </div>
        <div class="dbs-history-dialog-body">
            @foreach ($previousExams as $pastExam)
                <article class="dbs-history-item">
                    <div class="dbs-history-meta">
                        {{ $pastExam['exam_date'] }} · {{ $pastExam['exam_time'] }} WIB
                    </div>

                    <x-filament::badge :color="\App\Filament\Dosen\Pages\UnscoredScoring::examTypeBadgeColor($pastExam['exam_type_name'] ?? '')" class="mb-2">
                        {{ $pastExam['exam_type_name'] }}
                    </x-filament::badge>

                    <div class="dbs-history-title">{{ $pastExam['title'] }}</div>

                    <dl class="dbs-history-grid">
                        <div>
                            <dt>Nilai</dt>
                            <dd>{{ $pastExam['grade_display'] }}</dd>
                        </div>
                        <div>
                            <dt>Catatan revisi</dt>
                            <dd>{{ $pastExam['revision_note'] }}</dd>
                        </div>
                        <div>
                            <dt>Keputusan akhir</dt>
                            <dd>{{ $pastExam['final_decision'] }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
    </dialog>
@endif
