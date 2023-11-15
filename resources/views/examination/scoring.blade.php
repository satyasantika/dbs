@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Penilaian Ujian
                    <a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>

                <div class="card-body">
                    {{-- <div class="row mb-2">
                        <div class="col">
                            <a href="{{ route('chief.index') }}" class="btn btn-outline-primary btn-sm float-end">>> Halaman ketua penguji</a>
                        </div>
                    </div> --}}
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Nama</th>
                                    <th>Ujian</th>
                                    <th>Tanggal</th>
                                    <th>Nilai</th>
                                    <th>Huruf</th>
                                    <th>direvisi?</th>
                                    <th>catatan</th>
                                    <th>diterima?</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exam_scores as $exam_score)
                                <tr>
                                    <td>
                                        {{-- @if ($exam_score->registration->exam_pass) --}}
                                        <a href="{{ route('scoring.edit',$exam_score->id) }}" class="btn btn-sm btn-primary">nilai</a>
                                        {{-- @endif --}}
                                    <td>
                                        {{ $exam_score->mahasiswa }}
                                        @if ($exam_score->dosen == $exam_score->ketua)
                                        <br>
                                        <a href="{{ route('chief.show',$exam_score->exam_registration_id) }}" class="btn btn-outline-primary btn-sm float-end">>> Halaman ketua penguji</a>
                                        @endif
                                    </td>
                                    <td>{{ $exam_score->ujian }}</td>
                                    <td>{{ $exam_score->exam_date }}</td>
                                    <td class="text-center">{{ $exam_score->grade }}</td>
                                    <td class="text-center">{{ $exam_score->letter }}</td>
                                    <td class="text-center">{{ $exam_score->revision ? 'ya' : 'tidak' }}</td>
                                    <td>{{ is_null($exam_score->revision_note) ? 'tidak ada' : Str::of($exam_score->revision_note)->limit(20) }}</td>
                                    <td class="text-center">{{ $exam_score->pass_approved ? 'ya' : 'tidak' }}</td>
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
