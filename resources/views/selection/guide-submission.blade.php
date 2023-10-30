@extends('layouts.general')

@push('header')

@endpush
@push('body')
@if (!($max_guides == 5))
<form id="add-form" action="{{ route('guides.store') }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-success btn-sm">
        {{ __('+ tambah usulan pasangan pembimbing') }}
    </button>
</form>
@endif
<table class="table table-hover">
    <thead>
        <tr>
            <th scope="col">Pasangan</th>
            <th scope="col">Tahap</th>
            <th scope="col">Usulan Pembimbing</th>
            <th scope="col">status</th>
            <th scope="col">update terkini</th>
            {{-- <th scope="col">Action</th> --}}
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
                                        >{{ $guide1->allocation->lecture->name }} (Kuota: {{ $guide1->guide1_quota }})</option>
                                    @endforeach
                                @else
                                    {{-- tampilan semua pembimbing 2 --}}
                                    @foreach ($available_guide2s as $guide2)
                                    <option
                                        value="{{ $guide2->id }}"
                                        @selected($guide2->allocation->user_id == $guide->user_id)
                                        >{{ $guide2->allocation->lecture->name }} (Kuota: {{ $guide2->guide2_quota }})</option>
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
                                    >{{ $other_guide->allocation->lecture->name }} (Kuota: {{ $selection_guide->guide_order == 1 ? $other_guide->guide2_quota : $other_guide->guide1_quota }})</option>
                                @empty
                                <option value="0">{{ $guide_for_pair }}</option>
                                @endforelse
                            @endif
                        </select>

                        <button type="submit" class="btn btn-outline-primary btn-sm float-end">
                            {{ __('ajukan') }}
                        </button>
                    </form>
                @endif
                @if (!$guide->stage->final && $guide->user_id)
                <form id="cancel-form" action="{{ route('guides.cancel',$guide) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan membatalkan usulan pasangan ini?');">
                        {{ __('batalkan') }}
                    </button>
                </form>
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
                {{ $guide->created_at->format('d-m-Y H:i:s') }}
            </td>
            <td>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endpush
