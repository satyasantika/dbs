@push('title')
    Dashboard Penguji
@endpush
<h3>Selamat datang, {{ auth()->user()->name }}</h3>

<div class="row mb-3">
@can('respon examination score')
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header">Penilaian Ujian</div>
            <div class="card-body">
                untuk menilai ujian, silakan klik tombol berikut:<br>
                <a href="{{ route('scoring.index') }}" class="btn btn-sm btn-primary">Menilai Ujian</a>
            </div>
        </div>
    </div>
@endcan
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header">Status Pembimbing dan Penguji</div>
            <div class="card-body">
                <a href="{{ route('information.guide') }}" class="btn btn-sm btn-primary mb-2">Daftar</a> Bimbingan saya<br>
                <a href="{{ route('information.pass') }}" class="btn btn-sm btn-primary mb-2">Bukti</a> Membimbing / Menguji (untuk BKD)
            </div>
        </div>
    </div>
</div>


@can('respon selection guide')
<hr>
<div class="row">
    <div class="col-6">
        <div class="card">
            <div class="card-header">Info hasil pemilihan pembimbing di tahun 2023</div>
            <div class="card-body">
                untuk melihat hasil pemilihan pembimbing tahap 1,2,3,dst... silakan klik tombol berikut:<br>
                <a href="{{ route('respons.result') }}" class="btn btn-sm btn-primary">Hasil Pemilihan</a>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card">
            <div class="card-header">Terima/Tolak Usulan Pembimbing tahun 2023</div>
            <div class="card-body">
                untuk merespon pemilihan pembimbing di tahap 2 silakan klik tommbol berikut:<br>
                <a href="{{ route('respons.index') }}" class="btn btn-sm btn-primary">proses tahap 2</a>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="h5">
    Informasi kuota Pembimbing
</div>
@php
    $guide = App\Models\GuideAllocation::where('user_id',auth()->user()->id)->where('active',1)->first()
@endphp

<div class="card">
    <div class="card-header bg-light">Pembimbing 1</div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <div class="h1">
                    {{ $guide->guide1_quota }}
                </div>Kuota
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionGuide::where([
                        'user_id'=>auth()->user()->id,
                        'guide_order'=>1,
                        ])->count() }}
                </div>Usulan
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionStage::where([
                        'guide1_id'=>auth()->user()->id,
                        ])->count() }}
                </div>Acc
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionGuide::where([
                        'user_id'=>auth()->user()->id,
                        'guide_order'=>1,
                        'approved'=>0,
                        ])->count() }}
                </div>Ditolak
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionGuide::where([
                        'user_id'=>auth()->user()->id,
                        'guide_order'=>1,
                        'approved'=>NULL,
                        ])->count() }}
                </div>Menunggu
            </div>
        </div>
    </div>
</div>
<div class="card mt-3">
    <div class="card-header bg-light">Pembimbing 2</div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <div class="h1">
                    {{ $guide->guide2_quota }}
                </div>Kuota
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionGuide::where([
                        'user_id'=>auth()->user()->id,
                        'guide_order'=>2,
                        ])->count() }}
                </div>Usulan
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionStage::where([
                        'guide2_id'=>auth()->user()->id,
                        ])->count() }}
                </div>Acc
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionGuide::where([
                        'user_id'=>auth()->user()->id,
                        'guide_order'=>2,
                        'approved'=>0,
                        ])->count() }}
                </div>Ditolak
            </div>
            <div class="col">
                <div class="h1">
                    {{ App\Models\SelectionGuide::where([
                        'user_id'=>auth()->user()->id,
                        'guide_order'=>2,
                        'approved'=>NULL,
                        ])->count() }}

                </div>Menunggu
            </div>
        </div>
    </div>
</div>
@endcan

{{-- @includeWhen(auth()->user()->can('respon selection guide'), 'selection.guide-respon') --}}
