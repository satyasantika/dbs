@extends('layouts.app')
@push('title')
    Ujian belum diset
@endpush
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Jadwal Ujian Belum diset ke Penguji
                    <a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <span class="text-danger float-end">pastikan salah satu penguji berbintang, edit jika ketua belum ditentukan</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table small-font table-sm table-striped" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Peserta Ujian</th>
                                    <th>Ujian</th>
                                    <th>Waktu</th>
                                    <th>G1</th>
                                    <th>G2</th>
                                    <th>P1</th>
                                    <th>P2</th>
                                    <th>P3</th>
                                    {{-- <th></th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exam_registrations as $exam_registration)
                                <tr>
                                    <td>
                                        {{-- tombol set ujian --}}
                                            <form id="scoreset-form" action="{{ route('examregistrations.scoreset',$exam_registration) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="badge bg-success btn btn-sm" onclick="return confirm('Yakin akan set ujian?');">
                                                    {{ __('Set') }}
                                                </button>
                                                <a href="{{ route('examregistrations.edit',$exam_registration) }}" class="badge bg-primary btn btn-sm">E</a>
                                            </form>
                                    </td>
                                    <td>
                                        {{ $exam_registration->student->name ?? '-' }}
                                    </td>
                                    <td>{{ $exam_registration->examtype->name ?? '-' }}</td>
                                    <td>{{ $exam_registration->exam_date }} {{ $exam_registration->exam_time }}</td>
                                    <td>{{ $exam_registration->guide1->name ?? '-' }}{{ $exam_registration->guide1_id==$exam_registration->chief_id ? '*' : '' }}</td>
                                    <td>{{ $exam_registration->guide2->name ?? '-' }}{{ $exam_registration->guide2_id==$exam_registration->chief_id ? '*' : '' }}</td>
                                    <td>{{ $exam_registration->examiner1->name ?? '-' }}{{ $exam_registration->examiner1_id==$exam_registration->chief_id ? '*' : '' }}</td>
                                    <td>{{ $exam_registration->examiner2->name ?? '-' }}{{ $exam_registration->examiner2_id==$exam_registration->chief_id ? '*' : '' }}</td>
                                    <td>{{ $exam_registration->examiner3->name ?? '-' }}{{ $exam_registration->examiner3_id==$exam_registration->chief_id ? '*' : '' }}</td>

                                    {{-- <td><a href="{{ route('examregistrations.edit',$exam_registration) }}" class="badge bg-primary btn btn-sm">E</a></td> --}}
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="table-success">
                                        tidak ada jadwal ujian yang belum diset ke penguji
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
