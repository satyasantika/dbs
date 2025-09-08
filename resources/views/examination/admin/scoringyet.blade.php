@extends('layouts.app')
@push('title')
    Ujian belum dinilai
@endpush
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Dosen belum menilai
                    <a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    <th>Penguji</th>
                                    <th>Peserta Ujian</th>
                                    <th>Ujian</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exam_registrations as $exam_registration)
                                <tr>
                                    <td>
                                        @php
                                            $exam_scores = \App\Models\ViewExamScore::whereNull('pass_approved')->where('exam_registration_id',$exam_registration->id)->get();
                                        @endphp
                                        @foreach ($exam_scores as $exam_score)
                                        <a target="_blank" href="{{'https://api.whatsapp.com/send/?phone=62'
                                            .$exam_score->lecture->phone.'&text=Yth.%20Penguji%20'
                                            .$exam_score->mahasiswa.',%0A%0AMohon%20segera%20memberikan%20penilaian%20'
                                            .$exam_score->ujian.'%20pada%20'
                                            .$exam_score->registration->exam_date->isoFormat('dddd, D MMMM Y').'%20agar%20mahasiswa%20tersebut%20dapat%20segera%20mencetak%20lembar%20revisinya%0A%0Asilakan%20akses:%0A%0Ahttp://supportfkip.unsil.ac.id/dbsmatematika/examination/scoring/'
                                            .$exam_score->id.'/edit%0A%0A(jika%20eror%20saat%20buka%20link%20di%20handphone,%20pastikan%20awalannya%20http://%20bukan%20https://)'}}"
                                            class="badge rounded-pill bg-primary btn btn-sm">wa: {{ $exam_score->dosen }}</a>
                                        @endforeach
                                    </td>
                                    <td>{{ $exam_registration->mahasiswa }}</td>
                                    <td>{{ $exam_registration->kode_ujian }}</td>
                                    <td>{{ $exam_registration->exam_date }} {{ \Carbon\Carbon::create($exam_registration->exam_time)->isoFormat('HH:mm') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="table-success">
                                        tidak ada ujian yang belum dinilai
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
