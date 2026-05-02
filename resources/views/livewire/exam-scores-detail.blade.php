<div wire:poll.30s>
@php
    $scores    = $record->examScores;
    $total     = $scores->count();
    $pending   = $scores->filter(fn ($s) => is_null($s->grade))->count();
    $allScored = $total > 0 && $pending === 0;
@endphp

{{-- Info registrasi --}}
<div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm mb-4">
    <div>
        <span class="text-gray-500 dark:text-gray-400">Mahasiswa</span>
        <p class="font-semibold text-gray-900 dark:text-white">{{ $record->student?->name ?: '—' }}</p>
    </div>
    <div>
        <span class="text-gray-500 dark:text-gray-400">NIM</span>
        <p class="font-medium text-gray-900 dark:text-white">{{ $record->student?->username ?: '—' }}</p>
    </div>
    <div>
        <span class="text-gray-500 dark:text-gray-400">Tanggal &amp; Waktu</span>
        <p class="font-medium text-gray-900 dark:text-white">
            {{ $record->exam_date?->isoFormat('dddd, D MMMM Y') ?: '—' }}
            @if ($record->exam_time)· {{ \Carbon\Carbon::parse($record->exam_time)->format('H:i') }}@endif
            @if ($record->room)· Ruang {{ $record->room }}@endif
        </p>
    </div>
    <div>
        <span class="text-gray-500 dark:text-gray-400">Hasil Akhir</span>
        @php
            $regGrade  = $record->grade;
            $regLetter = $record->letter;
        @endphp
        <p class="font-medium text-gray-900 dark:text-white">
            @if ($regGrade !== null)
                <span style="display:inline-flex;align-items:center;border-radius:9999px;background:#1f2937;padding:1px 8px;font-size:11px;font-weight:700;color:#fff;margin-right:4px;">{{ $regGrade }}</span>
                <span class="font-bold">{{ $regLetter }}</span>
                @if ($record->pass_exam)
                    <span class="ml-1 inline-flex items-center rounded-full bg-success-100 dark:bg-success-900 px-2 py-0.5 text-xs font-semibold text-success-700 dark:text-success-300">Lulus</span>
                @else
                    <span class="ml-1 inline-flex items-center rounded-full bg-danger-100 dark:bg-danger-900 px-2 py-0.5 text-xs font-semibold text-danger-700 dark:text-danger-300">Belum Lulus</span>
                @endif
            @else
                <span class="text-gray-400">Menunggu penilaian</span>
            @endif
        </p>
    </div>
    @if ($record->title)
    <div class="col-span-2">
        <span class="text-gray-500 dark:text-gray-400">Judul</span>
        <p class="font-medium text-gray-900 dark:text-white">{{ $record->title }}</p>
    </div>
    @endif
</div>

{{-- Status penilaian --}}
@if ($total === 0)
    <div class="rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-200 dark:border-warning-800 px-4 py-2 text-sm text-warning-700 dark:text-warning-300 mb-4">
        Penguji belum diset — exam_scores belum dibuat.
    </div>
@elseif ($allScored)
    <div class="rounded-lg bg-success-50 dark:bg-success-950 border border-success-200 dark:border-success-800 px-4 py-2 text-sm text-success-700 dark:text-success-300 mb-4 flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
        Semua penilaian sudah lengkap.
        @if ($record->sent_at)
            <span class="ml-auto text-gray-500 dark:text-gray-400">Dikabari {{ $record->sent_at->locale('id')->isoFormat('D MMM Y, HH.mm') }}</span>
        @endif
    </div>
@else
    <div class="rounded-lg bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800 px-4 py-2 text-sm text-danger-700 dark:text-danger-300 mb-4 flex items-center gap-2">
        <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0"/>
        Penilaian belum lengkap — menunggu <strong class="mx-1">{{ $pending }}</strong> penguji.
    </div>
@endif

{{-- Tabel skor --}}
<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs">
                <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300 w-6">#</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Penguji</th>
                <th class="px-3 py-2 text-center font-semibold text-gray-600 dark:text-gray-300">Nilai</th>
                <th class="px-3 py-2 text-center font-semibold text-gray-600 dark:text-gray-300">Huruf</th>
                <th class="px-3 py-2 text-center font-semibold text-gray-600 dark:text-gray-300">Revisi</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Catatan Revisi</th>
                <th class="px-3 py-2 text-center font-semibold text-gray-600 dark:text-gray-300">Acc</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($scores as $score)
            @php
                $scored  = !is_null($score->grade);
                $isChief = $record->chief_id && $score->user_id == $record->chief_id;
                $waUrl   = $score->lecture?->phone
                    ? 'https://api.whatsapp.com/send/?phone=62' . $score->lecture->phone
                        . '&text=' . rawurlencode(
                            'Yth. Penguji ' . ($record->student?->name ?? '') . ',' . "\n\n"
                            . 'Mohon segera memberikan penilaian ' . ($record->examtype?->name ?? '')
                            . ' pada ' . ($record->exam_date?->isoFormat('dddd, D MMMM Y') ?? '')
                            . " agar mahasiswa tersebut dapat segera mencetak lembar revisinya\n\n"
                            . "silakan akses:\n\n"
                            . route('scoring.edit', ['scoring' => $score]) . "\n\n"
                            . '(jika eror saat buka link di handphone, pastikan awalannya http:// bukan https://)'
                        )
                    : null;
            @endphp
            <tr class="{{ $scored ? 'bg-success-50 dark:bg-success-950/30' : 'bg-warning-50 dark:bg-warning-950/30' }}">
                <td class="px-3 py-2 text-xs text-gray-400">{{ $score->examiner_order }}</td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        @if ($waUrl)
                            <a href="{{ $waUrl }}" target="_blank"
                               class="inline-flex items-center justify-center w-6 h-6 rounded text-success-600 hover:bg-success-100 dark:hover:bg-success-900 transition shrink-0"
                               title="Ingatkan via WhatsApp">
                                <svg viewBox="0 0 31 30" class="w-4 h-4" fill="currentColor"><path d="M30.3139 14.3245C30.174 10.4932 28.5594 6.864 25.8073 4.1948C23.0552 1.52559 19.3784 0.0227244 15.5446 4.10118e-06H15.4722C12.8904 -0.00191309 10.3527 0.668375 8.10857 1.94491C5.86449 3.22145 3.99142 5.06026 2.67367 7.28039C1.35592 9.50053 0.6389 12.0255 0.593155 14.6068C0.547411 17.1882 1.17452 19.737 2.41278 22.0024L1.09794 29.8703C1.0958 29.8865 1.09712 29.9029 1.10182 29.9185C1.10651 29.9341 1.11448 29.9485 1.12518 29.9607C1.13588 29.973 1.14907 29.9828 1.16387 29.9896C1.17867 29.9964 1.19475 29.9999 1.21103 30H1.23365L9.01561 28.269C11.0263 29.2344 13.2282 29.7353 15.4586 29.7346C15.6004 29.7346 15.7421 29.7346 15.8838 29.7346C17.8458 29.6786 19.7773 29.2346 21.5667 28.4282C23.3562 27.6218 24.9682 26.469 26.3098 25.0363C27.6514 23.6036 28.696 21.9194 29.3832 20.0809C30.0704 18.2423 30.3867 16.2859 30.3139 14.3245ZM15.8099 27.1487C15.6923 27.1487 15.5747 27.1487 15.4586 27.1487C13.4874 27.1511 11.5444 26.6795 9.79366 25.7735L9.39559 25.5654L4.11815 26.8124L5.09221 21.4732L4.86604 21.0902C3.78579 19.2484 3.20393 17.157 3.17778 15.0219C3.15163 12.8869 3.68208 10.7819 4.71689 8.91419C5.75171 7.0465 7.25518 5.48059 9.07924 4.37067C10.9033 3.26076 12.985 2.64514 15.1194 2.58444C15.238 2.58444 15.3571 2.58444 15.4767 2.58444C18.6992 2.59399 21.7889 3.86908 24.0802 6.13498C26.3715 8.40087 27.681 11.4762 27.7265 14.6984C27.7719 17.9205 26.5498 21.0316 24.3234 23.3612C22.0969 25.6909 19.0444 27.0527 15.8235 27.1532L15.8099 27.1487Z"/></svg>
                            </a>
                        @endif
                        <span class="font-medium text-gray-900 dark:text-white">{{ $score->lecture?->name ?: '(?)' }}</span>
                        @if ($isChief)
                            <span class="inline-flex items-center rounded-full bg-success-100 dark:bg-success-900 px-2 py-0.5 text-xs font-semibold text-success-700 dark:text-success-300">★ Ketua</span>
                        @endif
                        @if (!$scored)
                            <span class="inline-flex items-center rounded-full bg-warning-100 dark:bg-warning-900 px-2 py-0.5 text-xs text-warning-600 dark:text-warning-400">Belum menilai</span>
                        @endif
                    </div>
                </td>
                <td class="px-3 py-2 text-center">
                    @if (!is_null($score->grade))
                        <span style="display:inline-flex;align-items:center;border-radius:9999px;background:#1f2937;padding:1px 8px;font-size:11px;font-weight:700;color:#fff;">{{ $score->grade }}</span>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-center font-bold">
                    @php
                        $letter = $score->letter;
                        $letterColor = match(true) {
                            in_array($letter, ['A', 'A-'])       => '#16a34a',
                            in_array($letter, ['B+', 'B', 'B-']) => '#2563eb',
                            in_array($letter, ['C+', 'C', 'C-']) => '#d97706',
                            in_array($letter, ['D', 'E'])        => '#dc2626',
                            default                              => '#9ca3af',
                        };
                    @endphp
                    @if ($letter)
                        <span style="color:{{ $letterColor }}">{{ $letter }}</span>
                    @else
                        <span style="color:#9ca3af">—</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-center">
                    @if (is_null($score->revision))
                        <span style="color:#9ca3af">—</span>
                    @elseif ($score->revision)
                        <svg style="width:16px;height:16px;color:#16a34a;margin:auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    @else
                        <svg style="width:16px;height:16px;color:#9ca3af;margin:auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </td>
                <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400 max-w-[180px] whitespace-pre-wrap">{{ $score->revision_note ?: '—' }}</td>
                <td class="px-3 py-2 text-center">
                    @if (is_null($score->pass_approved))
                        <span style="color:#9ca3af">—</span>
                    @elseif ($score->pass_approved)
                        <svg style="width:16px;height:16px;color:#16a34a;margin:auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>
                    @else
                        <svg style="width:16px;height:16px;color:#dc2626;margin:auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l6 6M15 9l-6 6"/></svg>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">Belum ada data penilaian</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Tombol laporan --}}
@if ($total > 0)
<div class="mt-4 flex flex-wrap gap-2 items-center">
    <a href="{{ route('report.exam-chief', $record->id) }}" target="_blank"
       class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 hover:bg-success-700 px-3 py-1.5 text-xs font-semibold text-white transition">
        <x-heroicon-o-document-text class="w-3.5 h-3.5"/> Hasil Ujian
    </a>
    <a href="{{ route('report.revision-table', $record->id) }}" target="_blank"
       class="inline-flex items-center gap-1.5 rounded-lg bg-gray-600 hover:bg-gray-700 px-3 py-1.5 text-xs font-semibold text-white transition">
        <x-heroicon-o-table-cells class="w-3.5 h-3.5"/> Lembar Revisi
    </a>
    <a href="{{ route('report.revision-sign', $record->id) }}" target="_blank"
       class="inline-flex items-center gap-1.5 rounded-lg bg-gray-600 hover:bg-gray-700 px-3 py-1.5 text-xs font-semibold text-white transition">
        <x-heroicon-o-document-check class="w-3.5 h-3.5"/> Keterangan Revisi
    </a>
    @if ($record->exam_type_id == 3)
        <a href="{{ route('report.thesis-exam-chief', $record->id) }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 hover:bg-success-700 px-3 py-1.5 text-xs font-semibold text-white transition">
            <x-heroicon-o-document-text class="w-3.5 h-3.5"/> BA Hasil Ujian
        </a>
        <a href="{{ route('report.thesis-exam-by-lecture', $record->id) }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 hover:bg-success-700 px-3 py-1.5 text-xs font-semibold text-white transition">
            <x-heroicon-o-document-text class="w-3.5 h-3.5"/> Penilaian by Penguji
        </a>
        <a href="{{ route('report.thesis-rev-by-lecture', $record->id) }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 hover:bg-success-700 px-3 py-1.5 text-xs font-semibold text-white transition">
            <x-heroicon-o-document-text class="w-3.5 h-3.5"/> Revisi by Penguji
        </a>
    @endif
</div>
@endif
</div>
