@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Penilaian Ujian
                    <a href="{{ route('examregistrations.index') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>

                <div class="card-body">
                    {{ $examregistration->student->name }}<br>
                    {{ $examregistration->student->username }}<br>
                    {{ $examregistration->examtype->name }} ({{ $examregistration->exam_date->isoFormat('dddd, D MMMM Y') }} {{ $examregistration->exam_time }})<br>
                    {{ $examregistration->title }}

                    @if (!$empty_scores)
                        <div class="alert alert-success mt-3">
                            nilai ujian ini sudah lengkap
                        </div>
                    @else
                        <div class="alert alert-danger">
                            masih ada penguji yang belum menilai
                        </div>
                    @endif
                    <hr>
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Penguji</th>
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>5</th>
                                    <th>Nilai</th>
                                    <th>Huruf</th>
                                    <th>rev?</th>
                                    <th>catatan</th>
                                    <th>acc?</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exam_scores as $exam_score)
                                <tr class="{{ is_null($exam_score->grade) ? 'table-warning' : 'table-success' }}">
                                    <td>
                                        <a href="{{ route('scoring.edit',$exam_score->id) }}" class="btn btn-sm btn-outline-primary">E</a>
                                        <a target="_blank" href="{{'https://api.whatsapp.com/send/?phone=62'
                                            .$exam_score->lecture->phone.'&text=Yth.%20Penguji%20'
                                            .$examregistration->student->name.',%0A%0AMohon%20segera%20memberikan%20penilaian%20'
                                            .$examregistration->examtype->name.'%20pada%20'
                                            .$examregistration->exam_date->isoFormat('dddd, D MMMM Y').'%20agar%20mahasiswa%20tersebut%20dapat%20segera%20mencetak%20lembar%20revisinya%0A%0Asilakan%20akses:%0A%0Ahttp://supportfkip.unsil.ac.id/dbsmatematika/%0A%0A(jika%20eror%20saat%20buka%20link%20di%20handphone,%20pastikan%20awalannya%20http://%20bukan%20https://)'}}"
                                            class="badge rounded-pill bg-success btn btn-sm">wa</a>
                                    <td>
                                        {{ $exam_score->namadosen }}
                                        @if ($exam_score->dosen == $exam_score->ketua)
                                            <span class="badge rounded-pill bg-primary">ketua</span>
                                        @endif
                                    </td>
                                    <td>{{ $exam_score->score1 }}</td>
                                    <td>{{ $exam_score->score2 }}</td>
                                    <td>{{ $exam_score->score3 }}</td>
                                    <td>{{ $exam_score->score4 }}</td>
                                    <td>{{ $exam_score->score5 }}</td>
                                    <td class="text-center">{{ $exam_score->grade }}</td>
                                    <td class="text-center">{{ $exam_score->letter }}</td>
                                    <td class="text-center">{{ $exam_score->revision ? 'v' : 'x' }}</td>
                                    <td>{{ is_null($exam_score->revision_note) ? 'x' : Str::of($exam_score->revision_note)->limit(20) }}</td>
                                    <td class="text-center">{{ $exam_score->pass_approved ? 'v' : 'x' }}</td>
                                </tr>
                                @empty
                                belum ada data
                                @endforelse
                            </tbody>
                        </table>
                        <a target="_blank" href="{{ route('report.exam-chief',$examregistration->id) }}" class="btn btn-sm btn-success">Hasil Ujian</a>
                        <a target="_blank" href="{{ route('report.revision-table',$examregistration->id) }}" class="btn btn-sm btn-secondary">Lembar Revisi</a>
                        <a target="_blank" href="{{ route('report.revision-sign',$examregistration->id) }}" class="btn btn-sm btn-secondary ">Keterangan Revisi</a>
                    @if (!$empty_scores)
                        <a target="_blank" href="{{'https://api.whatsapp.com/send/?phone=62'
                            .$examregistration->student->phone.'&text=*INFORMASI%20Hasil%20'
                            .$examregistration->examtype->name.'*%0A%0ASaudara%20*'
                            .$examregistration->student->name.'*,%20Kami%20informasikan%20bahwa%20masing-masing%20dosen%20penguji%20telah%20menuliskan%20revisi%20'
                            .$examregistration->examtype->name.'%20('
                            .$examregistration->exam_date->isoFormat('dddd, D MMMM Y').')%20dan%20dapat%20dicetak%20pada%20sistem%20DBS%20berikut.%0A%0Ahttp://supportfkip.unsil.ac.id/dbsmatematika/%0A%0A(jika%20eror%20saat%20buka%20link%20di%20handphone,%20pastikan%20awalannya%20http://%20bukan%20https://)%0A%0ASilakan%20login%20menggunakan%0Ausername:%20NPM%0Apassword:%20tanggal%20lahir%20(format:%20YYYY-MM-DD)%0A%0ADemikian%20informasi%20ini%20Kami%20sampaikan.%20Atas%20perhatian%20Anda,%20Kami%20ucapkan%20terima%20kasih.%0A(ttd.)%20*Kajur%20Pendidikan%20Matematika*'}}"
                            class="btn btn-sm btn-success float-end">kabari</a>
                    @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
