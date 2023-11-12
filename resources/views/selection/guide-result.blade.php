@extends('layouts.general')

@push('header')
<a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
@endpush

@push('body')
<h5>Hasil Pemilihan Pembimbing Tahun 2023</h5>
Halaman ini berisi usulan hasil pemilihan pembimbing yang disetujui kedua calon pembimbing di tahun ini.
<table class="table table-hover">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Pengusul</th>
            <th scope="col">Pasangan Pembimbing</th>
            {{-- <th scope="col">Status</th> --}}
            <th scope="col">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($guides as $key => $guide)
            <tr>
                <th scope="row">{{ $key+1 }}</th>
                <td>
                    {{ $guide->student->name }}
                    <div class="row">
                        <div class="col">
                            <span class="badge bg-light text-dark">usulan Pembimbing {{ $guide->guide1_id == auth()->user()->id ? 1 : 2 }}</span>
                        </div>
                    </div>
                </td>
                <td>
                        <span class="badge bg-secondary">Pembimbing {{ $guide->guide1_id == auth()->user()->id ? 2 : 1 }}</span><br>
                        {{ $guide->guide1_id == auth()->user()->id ? $guide->guide2->name : $guide->guide1->name }}
                </td>
                {{-- <td>
                    <span class="badge bg-success">disetujui</span>
                    <br>{{ $guide->updated_at->format('d-m-Y H:i:s') }}
                </td> --}}
                <td>
                    Hasil Tahap {{ $guide->stage_order }}<br>
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
