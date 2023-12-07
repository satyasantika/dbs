@extends('report.master')
@section('report')
<div style="margin:auto;width:16cm;">
    <p class="text-center">
        <strong>SURAT KETERANGAN REVISI {{ strToUpper($examregistration->examtype->name) }}</strong>
    </p>
    <p>Yang bertanda tangan di bawah ini, Penguji {{ strToUpper($examregistration->examtype->name) }} menerangkan bahwa:</p>
    {{-- identitas peserta ujian --}}
    <table style="border: 1px solid white;line-height:1.2;">
        <tbody>
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td>{{ $examregistration->student->name }}</td>
            </tr>
            <tr>
                <td style="margin-right: 20px">Nomor Pokok Mahasiswa</td>
                <td>:</td>
                <td>{{ $examregistration->student->username }}</td>
            </tr>
            <tr>
                <td>Program Studi</td>
                <td>:</td>
                <td>Pendidikan Matematika</td>
            </tr>
            <tr style="vertical-align: top">
                <td>Judul {{ $examregistration->exam_type_id == 1 ? 'Proposal' : 'Skripsi' }}</td>
                <td>:</td>
                <td>{{ $examregistration->title }}</td>
            </tr>
        </tbody>
    </table>
    <span  @style('line-height: 1')>&nbsp;</span><br>
    {{-- <hr> --}}
    <p>Telah menyelesaikan perbaikan {{ $examregistration->exam_type_id == 1 ? 'Proposal' : 'Skripsi' }}
        yang telah disarankan saat {{ strToUpper($examregistration->examtype->name) }} pada tanggal {{ $examregistration->exam_date->isoFormat('DD MMMM Y') }}.</p>
    <p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
    <p style="text-align: right">
        Tasikmalaya, {{ $examregistration->exam_date->isoFormat('DD MMMM Y') }}<br>
    </p>
    {{-- tabel penguji --}}
    <div style="line-height:1">
        <table style="width:13cm;margin-left:auto;vertical-align:top;padding:10px;">
            <tbody>
                @forelse ($examscores as $key => $exam_score)
                <tr>
                    <td><span  @style('line-height: 2')>&nbsp;</span></td>
                </tr>
                <tr>
                    <td>Penguji {{ $key + 1 }} &nbsp;</td>
                    <td>:</td>
                    <td>{{ $exam_score->namadosen }}</td>
                    <td>………………………</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td>NIDN {{ $exam_score->lecture->username }}</td>
                </tr>
                @empty
                belum ada data
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- ketua penguji --}}
    <div class="float-end" @style('line-height:1')>
        <table>
            <tr>
                <td>
                    <br>
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
