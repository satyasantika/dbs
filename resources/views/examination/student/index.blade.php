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
                                    <th>Ujian</th>
                                    <th>Tanggal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($examinations as $examination)
                                <tr>
                                    <td>
                                        <a href="{{ route('exam.student.get-revision',$examination->id) }}" class="btn btn-sm btn-primary">rincian</a>
                                    <td>{{ $examination->ujian }}</td>
                                    <td>{{ $examination->exam_date }}</td>
                                    <td>
                                        @if ($examination->pass_exam)
                                        <a target="_blank" href="{{ route('report.revision-table',$examination->id) }}" class="btn btn-sm btn-outline-primary mr-2">Lembar Revisi</a>
                                        <a target="_blank" href="{{ route('report.revision-sign',$examination->id) }}" class="btn btn-sm btn-outline-primary">Keterangan Revisi</a>
                                        @else
                                        <div class="alert bg-warning">
                                            lembar revisi belum dapat dicetak, menunggu selesai penilaian
                                        </div>
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
