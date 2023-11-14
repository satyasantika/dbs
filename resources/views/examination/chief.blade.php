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
                    {{ $chief->examtype->name }}<br>
                    {{ $chief->student->name }} ({{{ $chief->student->username }}})<br>
                    Tanggal Ujian: {{ $chief->exam_date->format('d/m/Y') }}<br>
                    Pukul: {{ $chief->exam_time }}<br>
                    Tempat: {{ $chief->room }}<br>
                    Judul: {{ $chief->title }}<br>
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
                                <tr class="table-{{ is_null($examination->letter) ? 'danger' : 'success' }}">
                                    <td>{{ $examination->lecture->name }}</td>
                                    <td>{{ $examination->letter ?? "belum dinilai" }}</td>
                                    <td>{{ $examination->revision ? 'ya' : 'tidak' }}</td>
                                    <td>{{ $examination->pass_approved ? 'ya' : 'tidak' }}</td>
                                </tr>
                                @empty
                                belum ada data
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="row mb-2">
                        <div class="col">
                            {{-- <a href="{{ route('scoring.index') }}" class="btn btn-success btn-sm">Usulan dapat dilanjutkan</a>
                            <a href="{{ route('scoring.index') }}" class="btn btn-danger btn-sm float-end">Usulan ditolak, ulangi ujian proposal</a> --}}
                            @if ($chief->pass_exam == 1)
                                <div class="alert alert-success mt-3">
                                    nilai ini sudah diverifikasi oleh ketua majelis
                                </div>
                            @else
                                <form id="accept-form" action="{{ route('chief.pass',$chief->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('usulan ini diterima?');">
                                        {{ __('Klik untuk memverifikasi, jika semua nilai sudah terisi') }}
                                    </button>
                                </form>
                                {{-- <form id="decline-form" action="{{ route('chief.index',$chief->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('usulan ini ditolak?');">
                                        {{ __('Usulan ditolak, ujian lagi >>') }}
                                    </button>
                                </form> --}}
                            @endif

                        </div>
                    </div>
                    <hr>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
