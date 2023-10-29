@extends('layouts.setting-form')

@push('header')
    {{ $selectionguidegroup->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($selectionguidegroup->id)
        <form id="delete-form" action="{{ route('selectionguidegroups.destroy',$selectionguidegroup->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $selectionguidegroup->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $selectionguidegroup->id ? route('selectionguidegroups.update',$selectionguidegroup->id) : route('selectionguidegroups.store') }}" method="post">
    @csrf
    @if ($selectionguidegroup->id)
        @method('PUT')
    @endif
    {{-- dosen --}}
    <div class="row mb-3">
        <label for="user_id" class="col-md-4 col-form-label text-md-end">Dosen</label>
        <div class="col-md-6">
            <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required @disabled($selectionguidegroup->id)>
                <option value="">-- Pilih Dosen --</option>
                @foreach ($lectures as $lecture)
                <option value="{{ $lecture->id }}" @selected($lecture->id == $selectionguidegroup->allocation->user_id)>{{ $lecture->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- kuota menguji --}}
    <div class="row mb-3">
        <label for="group" class="col-md-4 col-form-label text-md-end">Kelompok Pembimbing</label>
        <div class="col-md-6">
            <input type="number" placeholder="0" value="{{ $selectionguidegroup->group }}" name="group" class="form-control" id="group">
        </div>
    </div>
    {{-- kuota pembimbing 1 --}}
    <div class="row mb-3">
        <label for="guide1_quota" class="col-md-4 col-form-label text-md-end">Kuota Pembimbing 1</label>
        <div class="col-md-6">
            <input type="number" placeholder="0" value="{{ $selectionguidegroup->guide1_quota }}" name="guide1_quota" class="form-control" id="guide1_quota">
        </div>
    </div>
    {{-- kuota pembimbing 2 --}}
    <div class="row mb-3">
        <label for="guide2_quota" class="col-md-4 col-form-label text-md-end">Kuota Pembimbing 2</label>
        <div class="col-md-6">
            <input type="number" placeholder="0" value="{{ $selectionguidegroup->guide2_quota }}" name="guide2_quota" class="form-control" id="guide2_quota">
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('selectionguidegroups.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
<div class="col-auto">
@if ($selectionguidegroup->id)
    <form id="activation-form" action={{ route('selectionguidegroups.activation',$selectionguidegroup->id) }} method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="user_id" value="{{ $selectionguidegroup->user_id }}">
        <button
            type="submit"
            class="btn btn-{{ ($selectionguidegroup->active ? 'outline-success' : 'outline-danger') }} btn-sm float-end">
            {{ ($selectionguidegroup->active ? 'aktif' : 'non-aktif') }}
        </button>
    </form>
@endif
</div>
@endpush
