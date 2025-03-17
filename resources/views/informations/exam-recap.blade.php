@php
    $angkatans = \App\Models\ViewGuideExaminer::where('year_generation','>=',2018)->distinct()->orderBy('year_generation')->pluck('year_generation');
@endphp
<div class="row justify-content-center mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                Rekap Kelulusan dan Ujian Skripsi per Tanggal {{ Carbon\Carbon::now()->isoFormat('D MMMM Y') }}
            </div>

            <div class="card-body">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                        <th scope="col">Thn</th>
                        <th scope="col" class="text-end">TOTAL</th>
                        <th scope="col" class="text-end">Lulus</th>
                        <th scope="col" class="text-end">Belum Lulus</th>
                        <th scope="col" class="text-end">Belum Sempro</th>
                        <th scope="col" class="text-end">Akan Semhas</th>
                        <th scope="col" class="text-end">Akan Sidang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($angkatans as $angkatan)
                        <tr>
                        <th scope="row">{{ $angkatan }}</th>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Total Mahasiswa']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)->count() }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Lulus']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)->whereNotNull('thesis_date')->count() }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Belum Lulus']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)->whereNull('thesis_date')->count() }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Belum Sempro']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)->whereNull('proposal_date')->whereNull('seminar_date')->whereNull('thesis_date')->count() }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Akan Semhas']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)->whereNotNull('proposal_date')->whereNull('seminar_date')->whereNull('thesis_date')->count() }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Akan Sidang']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)->whereNotNull('proposal_date')->whereNotNull('seminar_date')->whereNull('thesis_date')->count() }}
                            </a>
                        </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
