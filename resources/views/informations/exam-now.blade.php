@php
    $hari_ini = Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y');
    $tanggal_sekarang = Carbon\Carbon::now()->isoFormat('Y-MM-DD');
    $waktu_sekarang = Carbon\Carbon::now()->isoFormat('HH:mm:ss');
    $waktu_selesai = Carbon\Carbon::now()->subHour()->isoFormat('HH:mm:ss');
    $peserta = \App\Models\ViewExamRegistration::where('exam_date',$tanggal_sekarang)->where('exam_time', '>=', $waktu_selesai);
@endphp
@if ($peserta->exists())
<div class="row justify-content-center mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                Jadwal Ujian Hari ini ({{ $hari_ini }}) - Sedang dan akan berlangsung
            </div>

            <div class="card-body">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                        <th scope="col">#</th>
                        <th scope="col">Waktu</th>
                        <th scope="col">Mahasiswa</th>
                        <th scope="col">Judul</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($peserta->orderBy('exam_time')->get() as $index => $ujian)
                        <tr>
                        <th scope="row">{{ $index+1 }}</th>
                        <td>{{ Carbon\Carbon::createFromTimeString($ujian->exam_time)->isoFormat('HH:mm') }}</td>
                        <td>{{ $ujian->mahasiswa }}</td>
                        <td>{{ $ujian->title }}<br><span class="badge bg-primary">P1: {{ $ujian->guide1->name }}</span> <span class="badge bg-secondary">P2: {{ $ujian->guide2->name }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
