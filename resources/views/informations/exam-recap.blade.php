@php
    $angkatans = \App\Models\ViewGuideExaminer::where('year_generation','>=',2019)->distinct()->orderBy('year_generation')->pluck('year_generation');
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
                        @php
                            $daftar_sempro = \App\Models\ViewExamRegistration::where('year_generation',$angkatan)
                                ->where('pass_exam',0)
                                ->where('exam_type_id',1)
                                ->pluck('user_id');
                            $daftar_semhas = \App\Models\ViewExamRegistration::where('year_generation',$angkatan)
                                ->where('pass_exam',0)
                                ->where('exam_type_id',2)
                                ->pluck('user_id');
                            $daftar_sidang = \App\Models\ViewExamRegistration::where('year_generation',$angkatan)
                                ->where('pass_exam',0)
                                ->where('exam_type_id',3)
                                ->pluck('user_id');

                            $belum_daftar_sempro = \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)
                                ->whereNull('proposal_date')
                                ->whereNull('seminar_date')
                                ->whereNull('thesis_date')
                                ->get();
                            $sudah_daftar_sempro = \App\Models\ViewExamRegistration::where('year_generation',$angkatan)
                                ->where('pass_exam',0)
                                ->where('exam_type_id',1)
                                ->count();

                            $belum_daftar_semhas = \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)
                                ->whereNull('seminar_date')
                                ->whereNull('thesis_date')
                                ->whereNotIn('user_id',$daftar_sempro)
                                ->whereNotIn('user_id',$belum_daftar_sempro->pluck('user_id'))
                                ->get();
                            $sudah_daftar_semhas = \App\Models\ViewExamRegistration::where('year_generation',$angkatan)
                                ->where('pass_exam',0)
                                ->where('exam_type_id',2)
                                ->count();

                            $belum_daftar_sidang = \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)
                                ->whereNull('thesis_date')
                                ->whereNotIn('user_id',$daftar_semhas)
                                ->whereNotIn('user_id',$daftar_sempro)
                                ->whereNotIn('user_id',$belum_daftar_sempro->pluck('user_id'))
                                ->whereNotIn('user_id',$belum_daftar_semhas->pluck('user_id'))
                                ->get();
                            $sudah_daftar_sidang = \App\Models\ViewExamRegistration::where('year_generation',$angkatan)
                                ->where('pass_exam',0)
                                ->where('exam_type_id',3)
                                ->count();

                            $sudah_lulus = \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)
                                ->whereNotNull('thesis_date')
                                ->count();
                            $total = \App\Models\ViewGuideExaminer::where('year_generation',$angkatan)
                                ->count();
                        @endphp
                        <tr
                        >
                        <th scope="ro
                        w">{{ $angkatan }}</th
                        >
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Total Mahasiswa']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $total }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Lulus']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $sudah_lulus - $sudah_daftar_sidang }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Belum Lulus']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $total - $sudah_lulus + $sudah_daftar_sidang }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Belum Sempro']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $belum_daftar_sempro->count() + $sudah_daftar_sempro }} |
                                <span class="text-success">{{ $sudah_daftar_sempro }} reg</span>
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Akan Semhas']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $belum_daftar_semhas->count() + $sudah_daftar_semhas }} |
                                <span class="text-success">{{ $sudah_daftar_semhas }} reg</span>
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$angkatan,'context'=>'Mahasiswa Akan Sidang']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $belum_daftar_sidang->count() + $sudah_daftar_sidang }} |
                                <span class="text-success">{{ $sudah_daftar_sidang }} reg</span>
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
