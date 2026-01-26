@php
    $hari_ini = Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y');
    $tanggal_sekarang = Carbon\Carbon::now()->isoFormat('Y-MM-DD');
    $waktu_sekarang = Carbon\Carbon::now()->isoFormat('HH:mm:ss');
    $waktu_selesai = Carbon\Carbon::now()->subHour()->isoFormat('HH:mm:ss');
    $peserta = \App\Models\ViewExamRegistration::where('exam_date', '>=',$tanggal_sekarang)->where('exam_time', '>=', $waktu_selesai);
@endphp
@if ($peserta->exists())
<div class="row justify-content-center mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                Ujian Terjadwal
            </div>

            <div class="card-body">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                        <th scope="col">#</th>
                        <th scope="col">Waktu</th>
                        <th scope="col">Mahasiswa</th>
                        <th scope="col">Penguji</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($peserta->orderBy('exam_time')->get() as $index => $ujian)
                        <tr>
                        <th scope="row">{{ $index+1 }}</th>
                        <td>
                            {{ Carbon\Carbon::parse($ujian->exam_date)->isoFormat('dddd') }}
                            <br>{{ Carbon\Carbon::parse($ujian->exam_date)->isoFormat('D MMMM Y') }}
                            <br>{{ Carbon\Carbon::createFromTimeString($ujian->exam_time)->isoFormat('HH:mm') }}
                        </td>
                        <td>
                            {{ $ujian->mahasiswa }}
                            <br>Judul: <strong>{{ $ujian->title }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-{{ $ujian->examiner1_id==$ujian->chief_id ? 'primary' : 'secondary' }}">
                                Penguji 1: {{ $ujian->examiner1->name }}
                            </span>
                            <br><span class="badge bg-{{ $ujian->examiner2_id==$ujian->chief_id ? 'primary' : 'secondary' }}">
                                Penguji 2: {{ $ujian->examiner2->name }}
                            </span>
                            <br><span class="badge bg-{{ $ujian->examiner3_id==$ujian->chief_id ? 'primary' : 'secondary' }}">
                                Penguji 3: {{ $ujian->examiner3->name }}
                            </span>
                            <br><span class="badge bg-{{ $ujian->guide1_id==$ujian->chief_id ? 'primary' : 'secondary' }}">
                                Penguji 4: {{ $ujian->guide1->name }}
                            </span>
                            <br><span class="badge bg-{{ $ujian->guide2_id==$ujian->chief_id ? 'primary' : 'secondary' }}">
                                Penguji 5: {{ $ujian->guide2->name }}
                            </span>
                        </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
