@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Hasil Ujian
                    <a href="{{ route('exam.student.index') }}" class="btn btn-primary btn-sm float-end">kembali</a>
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
                                    <th>No.</th>
                                    <th>Penguji</th>
                                    <th>Keterangan Revisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exam_scores as $key => $exam_score)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $exam_score->namadosen }}</td>
                                    <td>
                                        @if ($exam_score->revision)
                                            {{ !is_null($exam_score->revision_note) ? $exam_score->revision_note : 'belum ditulis' }}
                                        @else
                                            tidak ada revisi
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
