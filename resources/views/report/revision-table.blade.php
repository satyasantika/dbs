@extends('report.master')
@section('report')
<div style="margin:auto;width:16cm;">
    <p class="text-center" >
        <strong>REVISI {{ strToUpper($examregistration->examtype->name) }}</strong>
    </p>
    {{-- identitas peserta ujian --}}
    <table style="border: 1px solid white; line-height:1.2;">
        <tbody>
            <tr>
                <td style="width: 4.2cm">Nama</td>
                <td>:</td>
                <td>{{ $examregistration->student->name }}</td>
            </tr>
            <tr>
                <td>Nomor Pokok Mahasiswa</td>
                <td>:</td>
                <td>{{ $examregistration->student->username }}</td>
            </tr>
            <tr>
                <td>Program Studi</td>
                <td>:</td>
                <td>Pendidikan Matematika</td>
            </tr>
            <tr>
                <td>Tanggal Ujian</td>
                <td>:</td>
                <td>{{ $examregistration->exam_date->isoFormat('DD MMMM Y') }}</td>
            </tr>
            <tr style="vertical-align: top">
                <td>Judul {{ $examregistration->exam_type_id == 1 ? 'Proposal' : 'Skripsi' }}</td>
                <td>:</td>
                <td style="text-align: justify">{{ $examregistration->title }}</td>
            </tr>
        </tbody>
    </table>
    <span  @style('line-height: 1')>&nbsp;</span><br>
    {{-- <hr> --}}
    {{-- tabel penguji --}}
    <div style="line-height:1">
        <table style="width:16cm">
            <thead>
                <tr>
                    <th style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:5px;width:0.3cm;" class="text-center">No.</th>
                    <th style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:5px;">Penguji</th>
                    <th style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:5px;">Keterangan Revisi</th>
                    <th style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:5px;width:2.7cm">Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($examscores as $key => $exam_score)
                <tr>
                    <td style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:5px;" class="text-center">{{ $key + 1 }}.</td>
                    <td style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:5px;">{{ $exam_score->namadosen }}<br>
                        NIDN {{ $exam_score->lecture->username }}<br>
                    </td>
                    <td style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:5px;">
                        @if ($exam_score->revision)
                            {{ !is_null($exam_score->revision_note) ? $exam_score->revision_note : 'belum ditulis' }}
                        @else
                            tidak ada revisi
                        @endif
                    </td>
                    <td style="border: 1px solid black;border-collapse:collapse;vertical-align:top;padding:10px;">
                        <span  @style('line-height: 3')>&nbsp;</span>
                    </td>
                </tr>
                @empty
                belum ada data
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- titimangsa --}}
    <div class="float-end" @style('line-height:1')>
        <table>
            <tr>
                <td>
                    <br>
                    Tasikmalaya, {{ $examregistration->exam_date->isoFormat('DD MMMM Y') }}<br>
                    Ketua Penguji,<br>
                    <span  @style('line-height: 4')>&nbsp;</span><br>
                    {{ $examregistration->chief->name }}<br>
                    NIDN {{ $examregistration->chief->username }}
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection

