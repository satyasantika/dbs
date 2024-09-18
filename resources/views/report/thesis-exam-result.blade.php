@extends('report.master')
@section('report')
<div style="margin:auto;width:18cm;">
    <p class="text-center" @style('font-size:12pt;font-weight:bold;')>
        BERITA ACARA SIDANG SKRIPSI<br>
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
        Mahasiswa tersebut diatas dinyatakan: {{ $examregistration->pass_exam ? '' : 'TIDAK' }}LULUS dengan nilai sebagai berikut,
    </p>
    {{-- tabel penguji --}}
    <div style="line-height:1.2; font-size:9pt">
        <table style="width: 100%;vertical-align: middle">
            <thead>
                <tr class="text-center" style="vertical-align: middle">
                    <th style="border: 1px solid black;border-collapse:collapse;padding:4px;width:.5cm;">NO</th>
                    <th style="border: 1px solid black;border-collapse:collapse;padding:4px;width:10cm;">PEMBIMBING & PENGUJI</th>
                    <th style="border: 1px solid black;border-collapse:collapse;padding:4px;width:1.3cm;">NILAI</th>
                    <th style="border: 1px solid black;border-collapse:collapse;padding:4px;">TANDA TANGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" style="border: 1px solid black;border-collapse:collapse;padding:4px;">NILAI PEMBIMBING</td>
                </tr>
                @foreach ($guidescores as $key => $guide_score)
                <tr style="vertical-align: top">
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-center">{{ $key + 1 }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">{{ $guide_score->namadosen }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">{{ $key==0 ? 'A = ' : 'B = ' }}{{ $guide_score->grade }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:10px;line-height:1.8"></td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="4" style="border: 1px solid black;border-collapse:collapse;padding:4px;">NILAI PENGUJI</td>
                </tr>
                @foreach ($examinerscores as $key => $examiner_score)
                <tr style="vertical-align: top">
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-center">{{ $key + 1 }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">{{ $examiner_score->namadosen }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">
                        @if($key==0)C = @elseif($key==1)D = @else E = @endif{{ $examiner_score->grade }}
                    </td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:10px;line-height:1.8"></td>
                </tr>
                @endforeach
                @php
                    $x = round(($guidescores->average('grade')+$examinerscores->average('grade'))/2,2);
                    $y = $last_seminar_score;
                    $z = round(($x+$y)/2,2);
                @endphp
                <tr>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-end" colspan="2">RATA-RATA</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">X = {{ $x }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">
                        X = (((A+B)/2)+((C+D+E)/3))/2
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-end" colspan="2">NILAI SEMINAR HASIL</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">Y = {{ $y }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">
                        Nilai SEMNAS sebelumnya
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;" class="text-end" colspan="2">TOTAL NILAI SIDANG</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;">Z = {{ $z }}</td>
                    <td style="border: 1px solid black;border-collapse:collapse;padding:4px;font-size:14pt" class="text-center">{{ $examregistration->letter }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <br>
    {{-- keterangan nilai --}}
    <div style="line-height:1.2; font-size:9pt">
        <table style="margin:auto;border: 1px solid white;width: 100%;vertical-align: middle">
            <tbody>
                <tr>
                    <td class="text-end"></td>
                    <td class="text-center" style="width: 0.3cm">A</td>
                    <td style="width: 1.1cm"><u>&gt;</u> 85.00</td>
                    <td>(Sangat Baik)</td>
                    <td class="text-end">45.00 &lt;</td>
                    <td class="text-center" style="width: 0.3cm">C+</td>
                    <td style="width: 1.1cm">&lt; 52.99</td>
                    <td>(Lebih dari Cukup)</td>
                </tr>
                <tr>
                    <td class="text-end">77.00 &lt;</td>
                    <td class="text-center">A-</td>
                    <td>&lt; 84.99</td>
                    <td>(Hampir Sangat Baik)</td>
                    <td class="text-end">37.00 &lt;</td>
                    <td class="text-center">C</td>
                    <td>&lt; 44.99</td>
                    <td>(Cukup)</td>
                </tr>
                <tr>
                    <td class="text-end">69.00 &lt;</td>
                    <td class="text-center">B+</td>
                    <td>&lt; 76.99</td>
                    <td>(Lebih Baik)</td>
                    <td class="text-end">29.00 &lt;</td>
                    <td class="text-center">C-</td>
                    <td>&lt; 36.99</td>
                    <td>(Hampir Cukup)</td>
                </tr>
                <tr>
                    <td class="text-end">61.00 &lt;</td>
                    <td class="text-center">B</td>
                    <td>&lt; 68.99</td>
                    <td>(Baik)</td>
                    <td class="text-end">21.00 &lt;</td>
                    <td class="text-center">D</td>
                    <td>&lt; 28.99</td>
                    <td>(Kurang)</td>
                </tr>
                <tr>
                    <td class="text-end">53.00 &lt;</td>
                    <td class="text-center">B</td>
                    <td>&lt; 60.99</td>
                    <td>(Hampir Baik)</td>
                    <td class="text-end"></td>
                    <td class="text-center">E</td>
                    <td><u>&lt;</u> 21.00</td>
                    <td>(Tidak Lulus)</td>
                </tr>
            </tbody>
        </table>
    </div>
    <span  @style('line-height: 1')>&nbsp;</span><br>
    <div class="table-responsive" style="margin: 0 auto;width=15cm; page-break-inside: avoid">
        {{-- pengesahan --}}
        <p>Demikian hal ini disampaikan untuk dapat dipergunakan sebagaimana mestinya.</p>
        <table style="margin:auto;border: 1px solid white;width: 100%;vertical-align: middle">
            <tbody>
                <tr>
                    <td style="width: 50%" class="text-center"><br>
                        Dekan,<br>
                        <span  @style('line-height: 4')>&nbsp;</span><br>
                        Dr. Nani Ratnaningsih, S.Pd., M.Pd.<br>
                        NIP 196605302021212001
                    </td>
                    <td style="width: 50%" class="text-center">Tasikmalaya, {{ $examregistration->exam_date->isoFormat('DD MMMM Y') }}<br>
                        Ketua Sidang,<br>
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
