@extends('layouts.general')

@push('header')

@endpush

@push('body')
Catatan:
<ol>
    <li>Anda dapat mengusulkan hingga (5) lima usulan pasangan pembimbing</li>
    <li>Tombol <b>+ tambah usulan pasangan pembimbing</b> hanya akan muncul jika Anda telah menentukan calon pembimbing suatu pasangan, jadi pastikan set nama calon dosen pembimbing 1 dan 2 sebelum menambah usulan baru untuk pasangan calon pembimbing</li>
    <li>Anda masih dapat membatalkan usulan nama dosen calon pembimbing selama belum ditentukan keputusan diterima/ditolaknya oleh calon pembimbing</li>
    <li>Pastikan tindak lanjuti dengan menghubungi calon pembimbing agar menerima kejelasan diterima/ditolaknya usulan.</li>
    <li>Jika usulan telah diterima dua calon pembimbing yang sepasang, maka usulan ini akan otomatis dicatatkan sistem sebagai calon pembimbing Anda, dan otomatis semua usulan pasangan lainnya diubah statusnya menjadi ditolak otomatis oleh sistem.</li>
    <li>Jika usulan ditolak oleh salah satu calon pembimbing, maka usulan pasangannya pun ini akan otomatis ditolak oleh sistem, dan Anda dapat mengajukan usulan baru untuk mengganti pasangan yang ditolak.</li>
</ol>
@if ($max_guides < 5 && !$stage->final && !$available_empty_guide)
    <form id="add-form" action="{{ route('guides.store') }}" method="POST">
        @csrf
        @can('create selection guides')
        <button type="submit" class="btn btn-success btn-sm">
            {{ __('+ tambah usulan pasangan pembimbing') }}
        </button>
        @endif
    </form>
@endif
<table class="table table-hover">
    <thead>
        <tr>
            <th scope="col">Pasangan</th>
            <th scope="col">Tahap</th>
            <th scope="col">Usulan Pembimbing</th>
            <th scope="col">Status</th>
            <th scope="col">Update terkini</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($guides as $guide)
        <tr>
            <th scope="row">{{ $guide->pair_order }}</th>
            <td>{{ $guide->stage->stage_order }}</td>
            <td>
                @if ($guide->user_id)
                    {{ $guide->guide->name }}
                @else
                    @if (!$guide->stage->final && !$guide->user_id)
                        <form id="p1-form" action="{{ route('guides.update',$guide) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <select id="guide_group_id" class="form-control @error('guide_group_id') is-invalid @enderror" name="guide_group_id">
                                <option value="0">-- Calon Pembimbing {{ $guide->guide_order }} --</option>
                                {{-- jika kedua pembimbing belum ditentukan --}}
                                @php
                                    // jika kedua pembimbing belum ditentukan
                                    $guide_check = \App\Models\SelectionGuide::where([
                                        'selection_stage_id' => $guide->selection_stage_id,
                                        'pair_order' => $guide->pair_order,
                                        ])
                                        ->whereNull('guide_group_id')
                                        ->count() == 2;
                                @endphp
                                @if ($guide_check)
                                    @if ($guide->guide_order == 1)
                                        {{-- tampilan semua pembimbing 1 --}}
                                        @foreach ($available_guide1s as $guide1)
                                        <option
                                            value="{{ $guide1->id }}"
                                            @selected($guide1->allocation->user_id == $guide->user_id)
                                            >{{ $guide1->allocation->lecture->name }} (Kuota: {{ $guide1->guide1_quota - $guide1->guide1_filled }})</option>
                                        @endforeach
                                    @else
                                        {{-- tampilan semua pembimbing 2 --}}
                                        @foreach ($available_guide2s as $guide2)
                                        <option
                                            value="{{ $guide2->id }}"
                                            @selected($guide2->allocation->user_id == $guide->user_id)
                                            >{{ $guide2->allocation->lecture->name }} (Kuota: {{ $guide2->guide2_quota - $guide2->guide2_filled }})</option>
                                        @endforeach
                                    @endif
                                @else
                                @php
                                    // jika kedua pembimbing belum ditentukan
                                    $selection_guide = \App\Models\SelectionGuide::where([
                                        'selection_stage_id' => $guide->selection_stage_id,
                                        'pair_order' => $guide->pair_order,
                                        ])
                                        ->whereNotNull('guide_group_id')
                                        ->first();
                                    if ($selection_guide->guide_order == 1) {
                                        // pasangan pembimbing 1
                                        $guide_for_pair = \App\Models\GuideGroup::where([
                                            'group' => $selection_guide->group->group,
                                            // 'guide_allocation_id' => $selection_guide->group->guide_allocation_id,
                                            'active' => 1,
                                            ])
                                            ->where('guide2_quota','>',0)
                                            ->orderBy('guide_allocation_id')
                                            ->get();
                                    } else {
                                        // pasangan pembimbing 2
                                        $guide_for_pair = \App\Models\GuideGroup::where([
                                            'group' => $selection_guide->group->group,
                                            // 'guide_allocation_id' => $selection_guide->group->guide_allocation_id,
                                            'active' => 1,
                                            ])
                                            ->where('guide1_quota','>',0)
                                            ->orderBy('guide_allocation_id')
                                            ->get();
                                    }
                                @endphp
                                    {{-- tampilan pasangan untuk pembimbing 1 --}}
                                    @forelse ($guide_for_pair as $other_guide)
                                    <option
                                        value="{{ $other_guide->id }}"
                                        @selected($other_guide->allocation->user_id == $guide->user_id)
                                        >{{ $other_guide->allocation->lecture->name }} (Kuota: {{ $selection_guide->guide_order == 1 ? $other_guide->guide2_quota - $other_guide->guide2_filled : $other_guide->guide1_quota - $other_guide->guide1_filled }})</option>
                                    @empty
                                    <option value="0">{{ $guide_for_pair }}</option>
                                    @endforelse
                                @endif
                            </select>

                            <button type="submit" class="btn btn-outline-primary btn-sm float-end">
                                {{ __('ajukan') }}
                            </button>
                        </form>
                    @else
                        usulan ini dibatalkan otomatis oleh sistem
                    @endif
                @endif
                @if (!$guide->stage->final && $guide->user_id)
                    @if (is_null($guide->approved))
                        <form id="cancel-form" action="{{ route('guides.cancel',$guide) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan membatalkan usulan pasangan ini?');">
                                {{ __('batalkan') }}
                            </button>
                        </form>
                    @endif
                @endif
            </td>
            <td>
                @if (is_null($guide->approved))
                    @if (!$guide->stage->final && $guide->user_id)
                        <span class="badge bg-warning">menunggu</span>
                    @endif
                @elseif ($guide->approved)
                    <span class="badge bg-success">disetujui</span>
                @else
                    <span class="badge bg-danger">ditolak</span>
                @endif
            </td>
            <td>
                {{ $guide->updated_at->format('d-m-Y H:i:s') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endpush
