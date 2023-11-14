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
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Nama</th>
                                    <th>Ujian</th>
                                    <th>Tanggal</th>
                                    {{-- <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>5</th> --}}
                                    <th>Nilai</th>
                                    <th>Huruf</th>
                                    <th>direvisi?</th>
                                    <th>catatan</th>
                                    <th>diterima?</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($examinations as $examination)
                                <tr>
                                    <td><a href="{{ route('scoring.edit',$examination->id) }}" class="btn btn-sm btn-primary">nilai</a>
                                    <td>{{ $examination->mahasiswa }}</td>
                                    <td>{{ $examination->ujian }}</td>
                                    <td>{{ $examination->exam_date }}</td>
                                    {{-- <td>{{ $examination->score01 }}</td>
                                    <td>{{ $examination->score02 }}</td>
                                    <td>{{ $examination->score03 }}</td>
                                    <td>{{ $examination->score04 }}</td>
                                    <td>{{ $examination->score05 }}</td> --}}
                                    <td class="text-center">{{ $examination->grade }}</td>
                                    <td class="text-center">{{ $examination->letter }}</td>
                                    <td class="text-center">{{ $examination->revision ? 'ya' : 'tidak' }}</td>
                                    <td>{{ is_null($examination->revision_note) ? 'tidak ada' : Str::of($examination->revision_note)->limit(20) }}</td>
                                    <td class="text-center">{{ $examination->pass_approved ? 'ya' : 'tidak' }}</td>
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
