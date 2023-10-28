@extends('layouts.setting-form')

@push('header')
    {{ $selectionstage->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($selectionstage->id)
        <form id="delete-form" action="{{ route('selectionstages.destroy',$selectionstage->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $selectionstage->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $selectionstage->id ? route('selectionstages.update',$selectionstage->id) : route('selectionstages.store') }}" method="post">
    @csrf
    @if ($selectionstage->id)
        @method('PUT')
    @endif
    {{-- tahapan --}}
    <div class="row mb-3">
        <label for="stage_order" class="col-md-4 col-form-label text-md-end">Tahapan Pemiliahan</label>
        <div class="col-md-6">
            <select id="stage_order" class="form-control @error('stage_order') is-invalid @enderror" name="stage_order" required @disabled($selectionstage->id)>
                <option value="">-- Pilih Tahapan --</option>
                @foreach ([1,2,3] as $order)
                <option value="{{ $order }}" @selected($selectionstage->stage_order == $order)>{{ $order }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- mahasiswa --}}
    <div class="row mb-3">
        <label for="user_id" class="col-md-4 col-form-label text-md-end">Mahasiswa</label>
        <div class="col-md-6">
            <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required @disabled($selectionstage->id)>
                <option value="">-- Pilih Mahasiswa --</option>
                @foreach ($students as $student)
                <option value="{{ $student->id }}" @selected($student->id == $selectionstage->user_id)>{{ $student->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- pembimbing 1 --}}
    <div class="row mb-3">
        <label for="guide1_id" class="col-md-4 col-form-label text-md-end">Pembimbing 1</label>
        <div class="col-md-6">
            <select id="guide1_id" class="form-control @error('guide1_id') is-invalid @enderror" name="guide1_id">
                <option value="">-- Pilih Pembimbing 1 --</option>
                @foreach ($lectures as $lecture)
                <option value="{{ $lecture->id }}" @selected($lecture->id == $selectionstage->guide1_id)>{{ $lecture->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- pembimbing 1 --}}
    <div class="row mb-3">
        <label for="guide2_id" class="col-md-4 col-form-label text-md-end">Pembimbing 2</label>
        <div class="col-md-6">
            <select id="guide2_id" class="form-control @error('guide2_id') is-invalid @enderror" name="guide2_id">
                <option value="">-- Pilih Pembimbing 2 --</option>
                @foreach ($lectures as $lecture)
                <option value="{{ $lecture->id }}" @selected($lecture->id == $selectionstage->guide2_id)>{{ $lecture->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- penguji 1 --}}
    <div class="row mb-3">
        <label for="examiner1_id" class="col-md-4 col-form-label text-md-end">Penguji 1</label>
        <div class="col-md-6">
            <select id="examiner1_id" class="form-control @error('examiner1_id') is-invalid @enderror" name="examiner1_id">
                <option value="">-- Pilih Penguji 1 --</option>
                @foreach ($lectures as $lecture)
                <option value="{{ $lecture->id }}" @selected($lecture->id == $selectionstage->examiner1_id)>{{ $lecture->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- penguji 2 --}}
    <div class="row mb-3">
        <label for="examiner2_id" class="col-md-4 col-form-label text-md-end">Penguji 2</label>
        <div class="col-md-6">
            <select id="examiner2_id" class="form-control @error('examiner2_id') is-invalid @enderror" name="examiner2_id">
                <option value="">-- Pilih Penguji 2 --</option>
                @foreach ($lectures as $lecture)
                <option value="{{ $lecture->id }}" @selected($lecture->id == $selectionstage->examiner2_id)>{{ $lecture->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- penguji 3 --}}
    <div class="row mb-3">
        <label for="examiner3_id" class="col-md-4 col-form-label text-md-end">Penguji 3</label>
        <div class="col-md-6">
            <select id="examiner3_id" class="form-control @error('examiner3_id') is-invalid @enderror" name="examiner3_id">
                <option value="">-- Pilih Penguji 3 --</option>
                @foreach ($lectures as $lecture)
                <option value="{{ $lecture->id }}" @selected($lecture->id == $selectionstage->examiner3_id)>{{ $lecture->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('selectionstages.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
