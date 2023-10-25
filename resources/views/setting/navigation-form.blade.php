@extends('layouts.setting-form')

@push('header')
    {{ $navigation->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($navigation->id)
        <form id="delete-form" action="{{ route('navigations.destroy',$navigation->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $navigation->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $navigation->id ? route('navigations.update',$navigation->id) : route('navigations.store') }}" method="post">
    @csrf
    @if ($navigation->id)
        @method('PUT')
    @endif
    <div class="row mb-3">
        <label for="navigationName" class="col-md-4 col-form-label text-md-end">Name</label>
        <div class="col-md-6">
            <input type="text" placeholder="navigation name" value="{{ $navigation->name }}" name="name" class="form-control" id="navigationName" required autofocus>
        </div>
    </div>
    <div class="row mb-3">
        <label for="url" class="col-md-4 col-form-label text-md-end">URL</label>
        <div class="col-md-6">
            <input type="text" placeholder="URL" value="{{ $navigation->url }}" name="url" class="form-control" id="url" required>
        </div>
    </div>
    <div class="row mb-3">
        <label for="order" class="col-md-4 col-form-label text-md-end">Urutan</label>
        <div class="col-md-6">
            <input type="text" placeholder="order" value="{{ $navigation->order }}" name="order" class="form-control" id="order">
        </div>
    </div>
    <div class="row mb-3">
        <label for="parent_id" class="col-md-4 col-form-label text-md-end">Kelompok Menu</label>
        <div class="col-md-6">
            <select id="parent_id" class="form-control @error('parent_id') is-invalid @enderror" name="parent_id">
            <option value="">-- Parent Menu --</option>
            @foreach ($parent_navs as $nav)
                <option value="{{ $nav->id }}" {{ $nav->id == $navigation->parent_id ? 'selected' : '' }}>{{ $nav->name }}</option>
            @endforeach
            </select>
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('navigations.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
