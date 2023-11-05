@extends('layouts.general')

@push('header')
<a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
@endpush

@push('body')
<h5>Proses Pemilihan Pembimbing Tahap 2 Tahun 2023</h5>
Halaman ini berisi usulan proses pemilihan pembimbing tahap 2 dari mahasiswa sekait dengan kesediaan untuk menjadi Dosen Pembimbing.
Bapak/Ibu dapat menerima atau menolak usulan tersebut selama kuota masih tersedia.
Perhatikan catatan berikut:
<ol>
    <li>Setiap mahasiswa diizinkan mengusulkan hingga (5) lima usulan pasangan pembimbing</li>
    <li>Jika Bapak/Ibu menerima usulan tersebut dan pasangan calon pembimbing juga menerimanya, maka pasangan calon pembimbing akan langsung ditetapkan</li>
    <li>Jika Bapak/Ibu menerima usulan tersebut, sementara pasangan calon pembimbing yang diusulkan menolak, maka usulan pasangan ini dibatalkan oleh sistem dan usulan ini tetap diarsipkan, sementara mahasiswa dapat mengajukan usulan lain pasangan baru.</li>
    <li>Jika Bapak/Ibu menolak usulan tersebut, maka secara otomatis usulan terhadap pasangan calon pembimbing lain juga ditolak.</li>
    <li>Jika satu usulan telah diterima oleh dua calon pembimbing yang berpasangan, maka usulan pasangan lain otomatis ditolak oleh sistem.</li>
</ol>
<table class="table table-hover">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Pengusul</th>
            <th scope="col">Pasangan Pembimbing</th>
            <th scope="col">Status</th>
            <th scope="col">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($guides as $key => $guide)
        <tr>
            <th scope="row">{{ $key+1 }}</th>
            <td>
                {{ $guide->stage->student->name }}
                <div class="row">
                    <div class="col">
                        <span class="badge bg-light text-dark">usulan Pembimbing {{ $guide->guide_order }}</span>
                    </div>
                @if (!$guide->stage->final)
                    @if (is_null($guide->approved))
                        <div class="col">
                            <form id="accept-form" action="{{ route('respons.accept',$guide) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Yakin akan menerima usulan ini?');">
                                    {{ __('terima') }}
                                </button>
                            </form>
                        </div>
                        <div class="col">
                            <form id="decline-form" action="{{ route('respons.decline',$guide) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin akan menolak usulan ini?');">
                                    {{ __('tolak') }}
                                </button>
                            </form>
                        </div>
                    @endif
                @endif
                </div>
            </td>
            <td>
                @php
                    $guide_order = $guide->guide_order == 1 ? 2 : 1;
                    $mypair = \App\Models\SelectionGuide::where([
                        'selection_stage_id'=>$guide->selection_stage_id,
                        'pair_order'=>$guide->pair_order,
                        'guide_order'=>$guide_order,
                    ])->first();
                @endphp
                @if ($mypair->user_id)
                    <span class="badge bg-secondary">Pembimbing {{ $guide_order }}</span><br>
                    {{ $mypair->guide->name }}
                @else
                    Belum diusulkan
                @endif
            </td>
            <td>
                @if (is_null($guide->approved))
                    <span class="badge bg-dark">diusulkan</span>
                @elseif ($guide->approved)
                    <span class="badge bg-success">disetujui</span>
                @else
                    <span class="badge bg-danger">ditolak</span>
                @endif
                <br>{{ $guide->updated_at->format('d-m-Y H:i:s') }}
            </td>
            <td>
                {{ $guide->information }}
                {{-- @if (!$guide->stage->final && !is_null($guide->approved))
                    @if ($guide->approved)
                    <div class="col">
                        <form id="decline-form" action="{{ route('respons.decline',$guide) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin akan menolak usulan ini?');">
                                {{ __('tolak (ralat)') }}
                            </button>
                        </form>
                    </div>
                    @else
                        <div class="col">
                            <form id="accept-form" action="{{ route('respons.accept',$guide) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Yakin akan menerima usulan ini?');">
                                    {{ __('diterima (ralat)') }}
                                </button>
                            </form>
                        </div>
                    @endif
                @endif --}}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endpush
