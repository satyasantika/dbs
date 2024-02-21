@extends('report.master')
@section('report')
<div style="margin:auto;width:18cm;">
    <p class="text-center" @style('font-size:12pt;font-weight:bold;')>
        BERITA ACARA HASIL {{ strToUpper($examregistration->examtype->name) }}<br>
    </p>
    <p style="text-align:justify;">
        Pada hari ini {{ $examregistration->exam_date->isoFormat('dddd, DD MMMM Y') }}
        pukul {{ $examregistration->exam_time }} WIB
        bertempat di Ruang Sidang Fakultas Keguruan dan Ilmu Pendidikan
        telah dilaksanakan {{ $examregistration->examtype->name }} bagi mahasiswa:
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
                <td>Nomor Pokok Mahasiswa</td>
                <td>:</td>
                <td>{{ $examregistration->student->username }}</td>
            </tr>
            <tr>
                <td>Program Studi</td>
                <td>:</td>
                <td>Pendidikan Matematika</td>
            </tr>
            <tr style="text-align:justify;vertical-align: top">
                <td>Judul {{ $examregistration->exam_type_id == 1 ? 'Proposal' : 'Skripsi' }}</td>
                <td>:</td>
                <td>{{ $examregistration->title }}</td>
            </tr>
        </tbody>
    </table>
    <span  @style('line-height: 1')>&nbsp;</span><br>
    {{-- <hr> --}}
    <p style="text-align:justify;">
        {{-- {{ ucFirst($examregistration->examtype->name) }} mahasiswa tersebut dinyatakan: {{ $examregistration->pass_exam == 1 ? '':'TIDAK ' }}LAYAK
        @if ($examregistration->exam_type_id == 1)
            dilanjutkan
        @endif
        @if ($examregistration->exam_type_id == 2)
            disidangkan
        @endif
        @if ($examregistration->exam_type_id == 3)
            diluluskan
        @endif --}}
        dengan pertimbangan para penguji sebagai berikut:
    </p>
    {{-- tabel penguji --}}
    <div style="line-height:1.2; font-size:9pt">
        <table style="width: 100%;vertical-align: middle">
            <thead>
                <tr class="text-center" style="vertical-align: middle">
                    <th rowspan="2" style="border: 1px solid black;border-collapse:collapse;padding:5px;">No.</th>
                    <th rowspan="2" style="border: 1px solid black;border-collapse:collapse;padding:5px;">Penguji</th>
                    <th colspan="5" style="border: 1px solid black;border-collapse:collapse;padding:5px;">Skor Komponen Penilaian</th>
                    <th rowspan="2" style="border: 1px solid black;border-collapse:collapse;padding:5px;width:1.6cm;">Nilai</th>
                    <th rowspan="2" style="border: 1px solid black;border-collapse:collapse;padding:5px;">Keterangan Revisi</th>
                </tr>
                <tr >
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px; " class="text-center">(1)</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px; " class="text-center">(2)</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px; " class="text-center">(3)</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px; " class="text-center">(4)</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px; " class="text-center">(5)</td>
                </tr>
            </thead>
            <tbody>
                @forelse ($examscores as $key => $exam_score)
                <tr style="vertical-align: top">
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-center">{{ $key + 1 }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;">{{ $exam_score->namadosen }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-center">{{ $exam_score->score1 }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-center">{{ $exam_score->score2 }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-center">{{ $exam_score->score3 }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-center">{{ $exam_score->score4 }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-center">{{ $exam_score->score5 }}</td>
                    {{-- <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-end">
                        orisinalitas:&nbsp;({{ $exam_score->score1 }}) &nbsp; | &nbsp;
                        tata&nbsp;tulis:&nbsp;({{ $exam_score->score2 }}) &nbsp; | &nbsp;
                        kemampuan&nbsp;menjelaskan:&nbsp;({{ $exam_score->score3 }}) &nbsp; | &nbsp;
                        penguasaan&nbsp;materi:&nbsp;({{ $exam_score->score4 }}) &nbsp; | &nbsp;
                        bobot&nbsp;ilmiah:&nbsp;({{ $exam_score->score5 }})
                    </td> --}}
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;" class="text-end">{{ $exam_score->grade.' ('.$exam_score->letter.')' }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:5px;">
                        @if ($exam_score->revision)
                            {{ !is_null($exam_score->revision_note) ? $exam_score->revision_note : 'belum ditulis' }}
                        @else
                            tidak ada revisi
                        @endif
                        <br>(rekomendasi:
                        @if (is_null($exam_score->pass_approved))
                            <strong>belum diputuskan</strong>
                        @else
                            <strong>{{ $exam_score->pass_approved == 1 ? '':'tidak ' }} layak {{ $exam_score->registration->exam_type_id == 3 ? 'diluluskan':'dilanjutkan' }}</strong>)
                        @endif
                    </td>
                </tr>
                @empty
                belum ada data
                @endforelse
                <tr>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:10px;" class="text-end" colspan="8">Nilai {{ $examregistration->examtype->name }}: <span style="font-weight: bold; font-size:2em">{{ $examregistration->grade }} ({{ $examregistration->letter }}) </span></td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:10px;" colspan="2">
                        Kesimpulan: <strong>{{ $examregistration->pass_exam == 1 ? '':'TIDAK ' }}
                        LAYAK {{ $examregistration->exam_type_id == 3 ? 'diluluskan':'dilanjutkan' }}</strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="9">Keterangan komponen penilaian: (1) orisinalitas (2) tata tulis (3) kemampuan menjelaskan (4) penguasaan materi (5) bobot ilmiah</td>
                </tr>
            </tbody>
        </table>
    </div>
    <span  @style('line-height: 1')>&nbsp;</span><br>
    <p>Demikian berita acara hasil {{ strToLower($examregistration->examtype->name) }} ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    {{-- pengesahan --}}
    <div class="table-responsive" style="margin: 0 auto;width=15cm;">
        <table style="margin:auto;border: 1px solid white;">
            <tbody>
                <tr>
                    <td style="width: 10cm">Mengetahui,</td>
                    <td>Tasikmalaya, {{ $examregistration->exam_date->isoFormat('DD MMMM Y') }}</td>
                </tr>
                <tr>
                    <td>Ketua Jurusan Pendidikan Matematika,<br>
                        <span  @style('line-height: 4')>&nbsp;</span><br>
                        Vepi Apiati, S.Pd., M.Pd.<br>
                        NIP 197504272021212004
                    </td>
                    <td>
                        Ketua Penguji,<br>
                        <span  @style('line-height: 4')>&nbsp;</span><br>
                        {{ $examregistration->chief->name }}<br>
                        NIDN {{ $examregistration->chief->username }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
