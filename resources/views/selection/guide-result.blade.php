@extends('layouts.general')

@push('header')
<a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
@endpush

@push('body')
<h5>Hasil Pemilihan Pembimbing Tahun 2023</h5>
Halaman ini berisi usulan hasil pemilihan pembimbing di tahun ini.
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
        @forelse ($guides as $key => $guide)
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
                    Hasil Tahap {{ $guide->stage->stage_order }}<br>
                    {{-- {{ $guide->information }} --}}
                </td>
            </tr>
        @empty
        <tr>
            <td colspan="5">
                Belum ada pengajuan dari mahasiswa

            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endpush
