@extends('layouts.app')
@push('title')
Histori Ujian {{ $student->name }}
@endpush
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-auto">
            <div class="card">
                <div class="card-header">
                    {{ __('Registrasi Ujian') }} {{ $student->nim }} {{ $student->mahasiswa }}
                    <a href="{{ route('guideexaminers.index') }}" class="btn btn-sm btn-primary float-end">kembali</a>
                </div>

                <div class="card-body">
                    <a href="{{ route('registrations.student',$student->id) }}" class="btn btn-sm btn-success">+ jadwal ujian</a>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>action</th>
                                <th>Tanggal ujian</th>
                                <th>Ujian</th>
                                <th>E1</th>
                                <th>E2</th>
                                <th>E3</th>
                                <th>G1</th>
                                <th>G2</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($examregistrations as $examregistration)
                                <tr class="table-{{ $examregistration->dilaporkan ? 'success' : '' }}">
                                    <td><a href="{{ route('examregistrations.edit',$examregistration->id) }}" class="btn btn-sm btn-primary">view</a></td>
                                    <td>{{ $examregistration->exam_date }}</td>
                                    <td>{{ $examregistration->kode_ujian }}</td>
                                    <td class="{{ $examregistration->chief==$examregistration->examiner1_id ? 'bg-warning' : '' }}">{{ $examregistration->penguji_1 }}</td>
                                    <td class="{{ $examregistration->chief==$examregistration->examiner2_id ? 'bg-warning' : '' }}">{{ $examregistration->penguji_2 }}</td>
                                    <td class="{{ $examregistration->chief==$examregistration->examiner3_id ? 'bg-warning' : '' }}">{{ $examregistration->penguji_3 }}</td>
                                    <td class="bg-info">{{ $examregistration->penguji_4 }}</td>
                                    <td class="bg-info">{{ $examregistration->penguji_5 }}</td>
                                    <td></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">belum ada data ujian</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
