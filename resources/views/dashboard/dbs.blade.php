@push('title')
    Dashboard DBS
@endpush
<h3>Selamat datang, {{ auth()->user()->name }}</h3>
<div class="row">
    <div class="col-auto">
        <div class="card">
            <div class="card-header">Manajemen Ujian</div>
            <div class="card-body">
                menu penjadwalan:<br>
                <a href="{{ \App\Filament\Resources\GuideExaminerResource::getUrl('index') }}" class="btn btn-sm btn-primary">Penjadwalan</a>
                <br>
                <hr>
                seluruh jadwal ujian (terkini):<br>
                <a href="{{ \App\Filament\Resources\ExamRegistrationResource::getUrl('index') }}" class="btn btn-sm btn-primary">Jadwal Ujian</a>
                <br>
                <hr>
                menu penguji yang belum menilai:<br>
                <a href="{{ url('/admin') }}" class="btn btn-sm btn-primary">belum menilai</a>
                <br>
                <hr>
                menu registrasi ujian belum diset ke penguji:<br>
                <a href="{{ \App\Filament\Resources\SetScoringToExaminerResource::getUrl() }}" class="btn btn-sm btn-primary">set jadwal ke penguji</a>
                <br>
            </div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card">
            <div class="card-header">Manajemen NUIR</div>
            <div class="card-body">
                <a href="{{ route('nuir-settings.index') }}" class="btn btn-sm btn-primary d-block mb-2">Konfigurasi NUIR</a>
                <a href="{{ route('nuir.review.index') }}" class="btn btn-sm btn-primary d-block mb-2">Review Submission</a>
                <a href="{{ route('nuir.proposals.index') }}" class="btn btn-sm btn-primary d-block mb-2">Monitor Usulan Calon Pembimbing</a>
                <a href="{{ \App\Filament\Resources\GuideExaminerResource::getUrl('index') }}" class="btn btn-sm btn-outline-primary d-block">Penjadwalan Filament</a>
            </div>
        </div>
    </div>
</div>
