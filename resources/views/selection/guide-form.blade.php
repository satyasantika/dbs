@extends('layouts.setting-form')

@push('header')
    {{ $guide->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($guide->id)
        <form id="delete-form" action="{{ route('guides.destroy',$guide->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan membatalkan usulan ke {{ $guide->name }}?');">
                {{ __('batalkan') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $guide->id ? route('guides.update',$guide->id) : route('guides.store') }}" method="post">
    @csrf
    @if ($guide->id)
        @method('PUT')
    @endif
    {{-- mahasiswa pada tahapan --}}
    <input type="hidden" name="selection_stage_id" value="{{ $guide->selection_stage_id }}">
    {{-- dosen dalam kelompok --}}
    <div class="row mb-3">
        <label for="guide_group_id" class="col-md-4 col-form-label text-md-end">Dosen</label>
        <div class="col-md-6">
            <select id="guide_group_id" class="form-control @error('guide_group_id') is-invalid @enderror" name="guide_group_id" required @disabled($guide->id)>
                <option value="">-- Pilih Dosen --</option>
                @foreach ($groups as $group)
                <option value="{{ $group->id }}" @selected($group->id == $guide->guide_group_id)>{{ $group->allocation->lecture->name }} (tahap {{ $group->stage_order }})</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- revisi usulan --}}
    <div class="row mb-3">
        <label for="guide_order" class="col-md-4 col-form-label text-md-end">Posisi</label>
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="guide_order" id="guide1" value="1" @checked($guide->guide_order == '1')>
                <label class="form-check-label" for="guide1">Pembimbing 1</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="guide_order" id="guide2" value="2" @checked($guide->guide_order == '2')>
                <label class="form-check-label" for="guide2">Pembimbing 2</label>
            </div>
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('guides.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
