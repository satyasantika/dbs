@php
    use App\Support\ExamFileLinkPaste as P;

    $labels = [
        P::STATUS_NEW => ['Diisi', 'success'],
        P::STATUS_REPLACE => ['Diganti', 'warning'],
        P::STATUS_SAME => ['Sudah sama', 'gray'],
        P::STATUS_NOT_FOUND => ['Tidak ujian di tanggal ini', 'danger'],
        P::STATUS_INVALID_LINK => ['Link tidak valid', 'danger'],
        P::STATUS_DUPLICATE => ['NPM ganda', 'danger'],
    ];

    $toSave = collect($rows)->whereIn('status', [P::STATUS_NEW, P::STATUS_REPLACE])->count();
@endphp

{{-- Panel admin tanpa tema Tailwind custom: style inline supaya tidak
     bergantung pada class yang belum tentu ada di CSS bawaan Filament. --}}
<style>
    .exam-file-paste-table { width: 100%; border-collapse: collapse; font-size: 12px; text-align: left; }
    .exam-file-paste-table th { position: sticky; top: 0; background: rgb(249 250 251); padding: .45rem .6rem; font-weight: 700; }
    .exam-file-paste-table td { padding: .45rem .6rem; border-top: 1px solid rgb(243 244 246); vertical-align: top; }
    .exam-file-paste-link { max-width: 18rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .exam-file-paste-old { color: rgb(156 163 175); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dark .exam-file-paste-table th { background: rgb(31 41 55); }
    .dark .exam-file-paste-table td { border-top-color: rgb(55 65 81); }
</style>

@if ($rows === [])
    <p style="font-size: 13px; color: rgb(107 114 128);">
        Tempel data di atas — preview muncul otomatis di sini.
    </p>
@else
    <p style="font-size: 13px; margin-bottom: .5rem;">
        {{ count($rows) }} baris terbaca · <strong>{{ $toSave }}</strong> akan disimpan
    </p>

    <div style="max-height: 20rem; overflow: auto; border: 1px solid rgb(229 231 235); border-radius: .5rem;">
        <table class="exam-file-paste-table">
            <thead>
                <tr>
                    <th>NPM</th>
                    <th>Nama</th>
                    <th>Link</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php([$label, $color] = $labels[$row['status']])
                    <tr>
                        <td style="font-family: ui-monospace, monospace;">{{ $row['npm'] }}</td>
                        <td>{{ $row['name'] ?? '—' }}</td>
                        <td class="exam-file-paste-link" title="{{ $row['link'] }}">
                            {{ $row['link'] !== '' ? $row['link'] : '—' }}
                            @if ($row['status'] === P::STATUS_REPLACE)
                                <div class="exam-file-paste-old" title="{{ $row['current'] }}">lama: {{ $row['current'] }}</div>
                            @endif
                        </td>
                        <td>
                            <x-filament::badge :color="$color" size="sm">{{ $label }}</x-filament::badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
