
<h3>Selamat datang, {{ auth()->user()->name }}</h3>
<div class="row">
    <div class="col-auto">
        <div class="card">
            <div class="card-header">Manajemen Ujian</div>
            <div class="card-body">
                untuk menjadwalkan ujian, silakan klik tombol berikut:<br>
                <a href="{{ route('examregistrations.index') }}" class="btn btn-sm btn-primary">Jadwal Ujian</a><br>
                <hr>
                untuk melihat para penguji yang belum menilai, silakan klik tombol berikut:<br>
                <a href="{{ route('get.examinerscoringyet') }}" class="btn btn-sm btn-primary">belum menilai</a>
            </div>
        </div>
    </div>
</div>
