@extends('layouts.setting-form')

@push('header')
    {{ $selectionelement->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
@endpush

@push('body')
<form id="formAction" action="{{ $selectionelement->id ? route('selectionelements.update',$selectionelement->id) : route('selectionelements.store') }}" method="post">
    @csrf
    @if ($selectionelement->id)
        @method('PUT')
    @endif
    {{-- mahasiswa pada tahapan --}}
    <div class="row mb-3">
        <label for="selection_stage_id" class="col-md-4 col-form-label text-md-end">Mahasiswa</label>
        <div class="col-md-6">
            <select id="selection_stage_id" class="form-control @error('selection_stage_id') is-invalid @enderror" name="selection_stage_id" required @disabled($selectionelement->id)>
                <option value="">-- Pilih Mahasiswa --</option>
                @foreach ($stages as $stage)
                <option value="{{ $stage->id }}" @selected($stage->id == $selectionelement->selection_stage_id)>{{ $stage->student->name }} (tahap {{ $stage->stage_order }})</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- revisi usulan --}}
    <div class="row mb-3">
        <label for="revision_order" class="col-md-4 col-form-label text-md-end">Tahapan Pemilihan</label>
        <div class="col-md-6">
            <select id="revision_order" class="form-control @error('revision_order') is-invalid @enderror" name="revision_order" required @disabled($selectionelement->id)>
                <option value="">-- Pilih Tahapan --</option>
                @foreach ([0,1,2,3] as $order)
                <option value="{{ $order }}" @selected($selectionelement->revision_order == $order)>{{ $order }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- unsur NUIR --}}
    <div class="row mb-3">
        <label for="element" class="col-md-4 col-form-label text-md-end">Unsur NUIR</label>
        <div class="col-md-6">
            <select id="element" class="form-control @error('element') is-invalid @enderror" name="element">
                <option value="">-- Pilih NUIR --</option>
                @foreach ($elements as $element)
                <option value="{{ $element }}" @selected($element == $selectionelement->element)>{{ $element }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- description --}}
    <div class="row mb-3">
        <label for="description" class="col-md-4 col-form-label text-md-end">Deskripsi NUIR</label>
        <div class="col-md-8">
            <textarea name="description" rows="20" class="form-control" id="description">{{ $selectionelement->description }}</textarea>
        </div>
    </div>
    {{-- link usulan --}}
    <div class="row mb-3">
        <label for="link" class="col-md-4 col-form-label text-md-end">Link</label>
        <div class="col-md-8">
            <textarea name="link" rows="3" class="form-control" id="link">{{ $selectionelement->link }}</textarea>
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('selectionelements.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
