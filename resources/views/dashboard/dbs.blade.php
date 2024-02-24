
<h3>Selamat datang, {{ auth()->user()->name }}</h3>
<div class="row">
    <div class="col-auto">
        <div class="card">
            <div class="card-header">Manajemen Ujian</div>
            <div class="card-body">
                untuk menjadwalkan ujian, silakan klik tombol berikut:<br>
                <a href="{{ route('examregistrations.index') }}" class="btn btn-sm btn-primary">Jadwal Ujian</a>
            </div>
        </div>
    </div>
</div>
