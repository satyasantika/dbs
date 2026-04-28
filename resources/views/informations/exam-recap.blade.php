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
                        @foreach ($rekap as $row)
                        <tr>
                        <th scope="row">{{ $row['angkatan'] }}</th>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$row['angkatan'],'context'=>'Total Mahasiswa']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $row['total'] }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$row['angkatan'],'context'=>'Mahasiswa Lulus']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $row['lulus'] }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$row['angkatan'],'context'=>'Mahasiswa Belum Lulus']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $row['belum_lulus'] }}
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$row['angkatan'],'context'=>'Mahasiswa Belum Sempro']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $row['belum_sempro'] }} |
                                <span class="text-success">{{ $row['belum_sempro_reg'] }} reg</span>
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$row['angkatan'],'context'=>'Mahasiswa Akan Semhas']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $row['akan_semhas'] }} |
                                <span class="text-success">{{ $row['akan_semhas_reg'] }} reg</span>
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('information.recap',['generation'=>$row['angkatan'],'context'=>'Mahasiswa Akan Sidang']) }}" rel="noopener noreferrer" class="text-primary" style="text-decoration: none">
                                {{ $row['akan_sidang'] }} |
                                <span class="text-success">{{ $row['akan_sidang_reg'] }} reg</span>
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
