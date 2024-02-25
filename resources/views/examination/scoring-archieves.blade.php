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
                    <div class="row mb-2">
                        <div class="col">
                            <a href="{{ route('scoring.index') }}" class="btn btn-primary btn-sm"><<< Belum input</a>
                        </div>
                    </div>
                    <div class="accordion" id="accordionExample">
                        @forelse ($exam_dates as $date)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $date }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $date }}" aria-expanded="false" aria-controls="collapse{{ $date }}">
                                        Ujian > {{ Carbon\Carbon::createFromFormat('Y-m-d',$date)->isoFormat('dddd, LL') }}
                                    </button>
                                </h2>
                                <div id="collapse{{ $date }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $date }}" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table small-font table-sm" id="profile-table" role="grid">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Nama</th>
                                                    <th>Nilai</th>
                                                    <th>Huruf</th>
                                                    <th>direvisi?</th>
                                                    <th>catatan</th>
                                                    <th>diterima?</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse (App\Models\ViewExamScore::where('user_id',auth()->id())->where('exam_date',$date)->whereNotNull('grade')->orderBy('exam_date','desc')->get(); as $exam_score)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('scoring.edit',$exam_score->id) }}" class="btn btn-sm btn-success">nilai</a>
                                                    </td>
                                                    <td>
                                                        {{ $exam_score->mahasiswa }}
                                                        @if ($exam_score->dosen == $exam_score->ketua)
                                                        <br>
                                                        <a href="{{ route('chief.show',$exam_score->exam_registration_id) }}" class="btn btn-outline-primary btn-sm float-end">>> Halaman ketua penguji</a>
                                                        @endif
                                                        <br><span class="badge bg-primary">{{ $exam_score->ujian }}</span>
                                                    </td>
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
                        @empty

                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
