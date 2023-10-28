@extends('layouts.setting-form')

@push('header')
    {{ $selectionelementcomment->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($selectionelementcomment->id)
        <form id="delete-form" action="{{ route('selectionelementcomments.destroy',$selectionelementcomment->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $selectionelementcomment->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $selectionelementcomment->id ? route('selectionelementcomments.update',$selectionelementcomment->id) : route('selectionelementcomments.store') }}" method="post">
    @csrf
    @if ($selectionelementcomment->id)
        @method('PUT')
    @endif
    <input type="hidden" name="">
    {{-- mahasiswa pada tahapan --}}
    <div class="row mb-3">
        <label for="verificator" class="col-md-4 col-form-label text-md-end">Mahasiswa</label>
        <div class="col-md-6">
            <select id="selection_element_id" class="form-control @error('selection_element_id') is-invalid @enderror" name="selection_element_id" required @disabled($selectionelementcomment->id)>
                <option value="">-- Pilih Mahasiswa --</option>
                @foreach ($elements as $element)
                <option value="{{ $element->id }}" @selected($element->id == $selectionelementcomment->selection_element_id)>
                    {{ $element->stage->student->name }} {{ $element->element }} (tahap {{ $element->stage->stage_order }})
                </option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- verifikator --}}
    <div class="row mb-3">
        <label for="user_id" class="col-md-4 col-form-label text-md-end">Verifikator</label>
        <div class="col-md-6">
            <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required @disabled($selectionelementcomment->id)>
                <option value="">-- Pilih Verifikator --</option>
                @foreach ($verificators as $verificator)
                <option value="{{ $verificator->id }}" @selected($verificator->id == $selectionelementcomment->user_id)>{{ $verificator->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- status verifikator --}}
    <div class="row mb-3">
        <label for="verificator" class="col-md-4 col-form-label text-md-end">Status</label>
        <div class="col-md-6">
            <select id="verificator" class="form-control @error('verificator') is-invalid @enderror" name="verificator" required @disabled($selectionelementcomment->id)>
                <option value="">-- Pilih Status --</option>
                @foreach (['dosen','dbs'] as $verificator)
                <option value="{{ $verificator }}" @selected($verificator == $selectionelementcomment->verificator)>{{ $verificator }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {{-- comment --}}
    <div class="row mb-3">
        <label for="comment" class="col-md-4 col-form-label text-md-end">Komentar {{ $element->element }} <br>{{ $element->description }}</label>
        <div class="col-md-8">
            <textarea name="comment" rows="20" class="form-control" id="comment">{{ $selectionelementcomment->comment }}</textarea>
        </div>
    </div>
    {{-- status revisi --}}
    <div class="row mb-3">
        <label for="link" class="col-md-4 col-form-label text-md-end">Direvisi?</label>
        <div class="col-md-8">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="revised" id="revisedYes" value="1" @checked($selectionelementcomment->revised == '1')>
                <label class="form-check-label" for="revisedYes">Perlu direvisi</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="revised" id="revisedNo" value="0" @checked($selectionelementcomment->revised == '0')>
                <label class="form-check-label" for="revisedNo">Tidak direvisi</label>
            </div>
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
