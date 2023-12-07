@extends('layouts.app')

@section('content')
<div class="container">
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif
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

                    @if ($examregistration->pass_exam == 1)
                        <div class="alert alert-success mt-3">
                            nilai ini sudah diverifikasi oleh ketua majelis
                        </div>
                    @else
                        <form id="accept-form" action="{{ route('chief.pass',$examregistration->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-outline-success btn-sm float-end" onclick="return confirm('usulan ini diterima?');">
                                {{ __('Klik untuk memverifikasi, jika semua nilai sudah terisi') }}
                            </button>
                        </form>
                    @endif
                    <hr>

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
                                <tr>
                                    <td>
                                        {{-- @if ($exam_score->registration->exam_pass) --}}
                                        <a href="{{ route('scoring.edit',$exam_score->id) }}" class="btn btn-sm btn-outline-primary">E</a>
                                        {{-- @endif --}}
                                    <td>
                                        {{ $exam_score->namadosen }}
                                        @if ($exam_score->dosen == $exam_score->ketua)
                                        *
                                        {{-- <br> --}}
                                        {{-- <a href="{{ route('chief.show',$exam_score->exam_registration_id) }}" class="btn btn-outline-primary btn-sm float-end">>> Halaman ketua penguji</a> --}}
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


                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
