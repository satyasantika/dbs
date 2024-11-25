@extends('report.master-no-header')

@push('title')
    Penilaian {{ $examregistration->examtype->code }} {{ $examregistration->student->name }}
@endpush

@section('report')
@foreach ($examscores as $examscore)
<div class="page-break">
    @include('report.header-fkip')
    <div style="margin:auto;width:18cm;">
        <p class="text-center" @style('font-size:12pt;font-weight:bold;')>
            PENILAIAN SKRIPSI<br>
        </p>
        <p style="text-align:justify;">
            Pada hari ini {{ $examregistration->exam_date->isoFormat('dddd, DD MMMM Y') }}
            pukul {{ \Carbon\Carbon::create($examregistration->exam_time)->isoFormat('HH:mm') }} WIB
            bertempat di Ruang Sidang {{ $examregistration->room }} Fakultas Keguruan dan Ilmu Pendidikan
            telah dilaksanakan SIDANG SKRIPSI atas nama,
        </p>
        {{-- identitas --}}
        <table style="line-height:1.2">
            <tbody>
                <tr>
                    <td style="width: 4.4cm">Nama</td>
                    <td>:</td>
                    <td>{{ $examregistration->student->name }}</td>
                </tr>
                <tr>
                    <td>NPM</td>
                    <td>:</td>
                    <td>{{ $examregistration->student->username }}</td>
                </tr>
                <tr>
                    <td>Program Studi</td>
                    <td>:</td>
                    <td>Pendidikan Matematika</td>
                </tr>
            </tbody>
        </table>
        <p style="align-content: justify"><em><u>JUDUL SKRIPSI:</u></em><br>{{ $examregistration->title }}</p>
        {{-- <span  @style('line-height: 1')>&nbsp;</span><br> --}}
        {{-- <hr> --}}
        <p style="text-align:justify;">
            Mahasiswa tersebut di atas mendapatkan nilai sebagai berikut,
        </p>
        {{-- tabel penguji --}}
        <div style="line-height:1.2; font-size:12pt">
            <table style="width: 100%;vertical-align: middle">
                <thead>
                    <tr class="text-center" style="vertical-align: middle">
                        <th style="border: 1px solid black;border-collapse:collapse;padding:4px;width:.5cm;">NO</th>
                        <th style="border: 1px solid black;border-collapse:collapse;padding:4px;width:10cm;">ASPEK YANG DINILAI</th>
                        <th style="border: 1px solid black;border-collapse:collapse;padding:4px;">NILAI (x)</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- skor penilaian --}}
                    @foreach ($form_items as $item)
                    @php
                        if ($item->exam_type_id == 3) {
                            $order = ($item->id) - 10;
                        }
                        $item_order = 'score'.$order;
                    @endphp
                    <tr>
                        <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">{{ $order }}.</td>
                        <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">{{ $item->name }}</td>
                        <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-center">{{ $examscore->$item_order }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-end" colspan="2">Jumlah</td>
                        <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-center">
                            {{ $examscore->score1 + $examscore->score2 + $examscore->score3 + $examscore->score4 + $examscore->score5 }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-center" colspan="2">Nilai Ujian Skripsi = Jumlah(x)/5</td>
                        <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-center">{{ round($examscore->grade,2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <br>
        <div class="table-responsive" style="margin: 0 auto;width=15cm; page-break-inside: avoid">
            {{-- pengesahan --}}
            <p>Demikian hal ini disampaikan untuk dapat dipergunakan sebagaimana mestinya.</p>
            <table style="margin:auto;border: 1px solid white;width: 100%;vertical-align: middle">
                <tbody>
                    <tr>
                        <td style="width: 50%" class="text-center"><br>

                        </td>
                        <td style="width: 50%" class="text-center">Tasikmalaya, {{ $examscore->registration->exam_date->isoFormat('DD MMMM Y') }}<br>
                            @if ($examscore->examiner_order == 1) Penguji I,@endif
                            @if ($examscore->examiner_order == 2) Penguji II,@endif
                            @if ($examscore->examiner_order == 3) Penguji III,@endif
                            @if ($examscore->examiner_order == 4) Pembimbing I,@endif
                            @if ($examscore->examiner_order == 5) Pembimbing II,@endif
                            <br>
                            <span  @style('line-height: 4')>&nbsp;</span><br>
                            {{ $examscore->lecture->name }}<br>
                            NIDN {{ $examscore->lecture->username }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach
@endsection
