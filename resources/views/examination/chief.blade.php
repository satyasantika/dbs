@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Halaman Ketua Penguji
                    <a href="{{ route('scoring.index') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>

                <div class="card-body">
                    <h4>{{ $chief->examtype->name }}</h4>
                    <h3 class="text-primary">{{ $chief->student->name }} ({{{ $chief->student->username }}})</h3>
                    <div class="row mb-2">
                        <div class="col-2">Tanggal</div>
                        <div class="col">{{ Carbon\Carbon::parse($chief->exam_date)->isoFormat('LL') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-2">Pukul</div>
                        <div class="col">{{ Carbon\Carbon::parse($chief->exam_time)->isoFormat('LT') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-2">Tempat</div>
                        <div class="col">Ruang {{ $chief->room }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-2">Judul</div>
                        <div class="col">{{ $chief->title }}</div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Nilai</th>
                                    <th>direvisi?</th>
                                    <th>lanjutkan?</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($examinations as $examination)
                                <tr class="table-{{ is_null($examination->letter) ? 'danger' : '' }}">
                                    <td>
                                        <a target="_blank" href="{{'https://api.whatsapp.com/send/?phone=62'
                                            .$examination->lecture->phone.'&text=Yth.%20Penguji%20'
                                            .$examination->mahasiswa.',%0A%0AMohon%20segera%20memberikan%20penilaian%20'
                                            .$examination->viewregistration->ujian.'%20pada%20'
                                            .$examination->registration->exam_date->isoFormat('dddd, D MMMM Y').'%20agar%20mahasiswa%20tersebut%20dapat%20segera%20mencetak%20lembar%20revisinya%0A%0Asilakan%20akses:%0A%0Ahttp://supportfkip.unsil.ac.id/dbsmatematika/examination/scoring/'
                                            .$examination->id.'/edit%0A%0A(jika%20eror%20saat%20buka%20link%20di%20handphone,%20pastikan%20awalannya%20http://%20bukan%20https://)'}}"
                                            class="btn btn-outline-success btn-sm ">wa</a>
                                        {{ $examination->lecture->name }}
                                    </td>
                                    <td>{{ $examination->letter ?? "belum dinilai" }}</td>
                                    <td>@if ($examination->revision)
                                            <span class="badge bg-warning text-dark"><i class="bi bi-quote"></i> ada revisi</span>
                                        @else
                                            <span class="badge bg-success"><i class="bi bi-shield-check"></i> tanpa revisi</span>
                                        @endif
                                    </td>
                                    <td>@if ($examination->pass_approved)
                                            <span   span class="badge bg-success"><i class="bi bi-check-circle"></i> lanjut</span>
                                        @else
                                            <span class="badge bg-danger"><i class="bi bi-question-diamond-fill"></i> gagal</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                belum ada data
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
