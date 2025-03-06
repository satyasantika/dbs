@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Hasil Ujian @if(!is_null($examination)) {{ $examination->mahasiswa }} @endif
                    <a href="{{ route('exam.result') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    {{-- <th></th> --}}
                                    <th>Ujian</th>
                                    <th>Tanggal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!is_null($examination))
                                <tr>
                                    <td>{{ $examination->ujian }}</td>
                                    <td>{{ $examination->exam_date }}</td>
                                    <td>
                                        @if ($examination->pass_exam)
                                        <a target="_blank" href="{{ route('report.revision-table',$examination->id) }}" class="btn btn-sm btn-outline-primary mr-2">Lembar Revisi</a>
                                        <a target="_blank" href="{{ route('report.revision-sign',$examination->id) }}" class="btn btn-sm btn-outline-primary">Keterangan Revisi</a>
                                        @else
                                        lembar revisi belum dapat dicetak, menunggu selesai penilaian
                                        @endif
                                    </td>
                                </tr>
                                @else
                                <div class="alert bg-warning">
                                    npm dan tanggal ujian tidak ditemukan, silakan ulangi lagi
                                    <a href="{{ route('exam.result') }}" class="btn btn-primary btn-sm">isi lagi</a>
                                </div>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
