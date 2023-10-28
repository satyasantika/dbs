@extends('layouts.setting-form')

@push('header')
    {{ $selectionguideallocation->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($selectionguideallocation->id)
        <form id="delete-form" action="{{ route('selectionguideallocations.destroy',$selectionguideallocation->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $selectionguideallocation->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $selectionguideallocation->id ? route('selectionguideallocations.update',$selectionguideallocation->id) : route('selectionguideallocations.store') }}" method="post">
    @csrf
    @if ($selectionguideallocation->id)
        @method('PUT')
    @endif
    {{-- dosen --}}
    <div class="row mb-3">
        <label for="user_id" class="col-md-4 col-form-label text-md-end">Dosen</label>
        <div class="col-md-6">
            <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required @disabled($selectionguideallocation->id)>
                <option value="">-- Pilih Dosen --</option>
                @foreach ($lectures as $lecture)
                <option value="{{ $lecture->id }}" @selected($lecture->id == $selectionguideallocation->user_id)>{{ $lecture->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- tahun --}}
    <div class="row mb-3">
        <label for="year" class="col-md-4 col-form-label text-md-end">Tahun</label>
        <div class="col-md-6">
            <select id="year" class="form-control @error('year') is-invalid @enderror" name="year" required @disabled($selectionguideallocation->id)>
                <option value="">-- Pilih Tahun --</option>
                @foreach ([2023] as $year)
                <option value="{{ $year }}" @selected($selectionguideallocation->year == $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- kuota pembimbing 1 --}}
    <div class="row mb-3">
        <label for="guide1_quota" class="col-md-4 col-form-label text-md-end">Kuota Pembimbing 1</label>
        <div class="col-md-6">
            <input type="number" placeholder="0" value="{{ $selectionguideallocation->guide1_quota }}" name="guide1_quota" class="form-control" id="guide1_quota">
        </div>
    </div>
    {{-- kuota pembimbing 2 --}}
    <div class="row mb-3">
        <label for="guide2_quota" class="col-md-4 col-form-label text-md-end">Kuota Pembimbing 2</label>
        <div class="col-md-6">
            <input type="number" placeholder="0" value="{{ $selectionguideallocation->guide2_quota }}" name="guide2_quota" class="form-control" id="guide2_quota">
        </div>
    </div>
    {{-- kuota menguji --}}
    <div class="row mb-3">
        <label for="examiner_quota" class="col-md-4 col-form-label text-md-end">Kuota Menguji</label>
        <div class="col-md-6">
            <input type="number" placeholder="0" value="{{ $selectionguideallocation->examiner_quota }}" name="examiner_quota" class="form-control" id="examiner_quota">
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('selectionguideallocations.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
<div class="col-auto">
@if ($selectionguideallocation->id)
    <form id="activation-form" action={{ route('selectionguideallocations.activation',$selectionguideallocation->id) }} method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="user_id" value="{{ $selectionguideallocation->user_id }}">
        <button
            type="submit"
            class="btn btn-{{ ($selectionguideallocation->active ? 'outline-success' : 'outline-danger') }} btn-sm float-end">
            {{ ($selectionguideallocation->active ? 'aktif' : 'non-aktif') }}
        </button>
    </form>
@endif
</div>
@endpush
