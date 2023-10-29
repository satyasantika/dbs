@extends('layouts.setting-form')

@push('header')
    {{ $selectionguide->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($selectionguide->id)
        <form id="delete-form" action="{{ route('selectionguides.destroy',$selectionguide->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $selectionguide->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $selectionguide->id ? route('selectionguides.update',$selectionguide->id) : route('selectionguides.store') }}" method="post">
    @csrf
    @if ($selectionguide->id)
        @method('PUT')
    @endif
    {{-- mahasiswa pada tahapan --}}
    <div class="row mb-3">
        <label for="selection_stage_id" class="col-md-4 col-form-label text-md-end">Mahasiswa</label>
        <div class="col-md-6">
            <select id="selection_stage_id" class="form-control @error('selection_stage_id') is-invalid @enderror" name="selection_stage_id" required @disabled($selectionguide->id)>
                <option value="">-- Pilih Mahasiswa --</option>
                @foreach ($stages as $stage)
                <option value="{{ $stage->id }}" @selected($stage->id == $selectionguide->selection_stage_id)>{{ $stage->student->name }} (tahap {{ $stage->stage_order }})</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- dosen dalam kelompok --}}
    <div class="row mb-3">
        <label for="guide_group_id" class="col-md-4 col-form-label text-md-end">Dosen</label>
        <div class="col-md-6">
            <select id="guide_group_id" class="form-control @error('guide_group_id') is-invalid @enderror" name="guide_group_id" required @disabled($selectionguide->id)>
                <option value="">-- Pilih Dosen --</option>
                @foreach ($groups as $group)
                <option value="{{ $group->id }}" @selected($group->id == $selectionguide->guide_group_id)>{{ $group->allocation->lecture->name }} (tahap {{ $group->stage_order }})</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- revisi usulan --}}
    <div class="row mb-3">
        <label for="guide_order" class="col-md-4 col-form-label text-md-end">Posisi</label>
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="guide_order" id="guide1" value="1" @checked($selectionguide->guide_order == '1')>
                <label class="form-check-label" for="guide1">Pembimbing 1</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="guide_order" id="guide2" value="2" @checked($selectionguide->guide_order == '2')>
                <label class="form-check-label" for="guide2">Pembimbing 2</label>
            </div>
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('selectionguides.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
