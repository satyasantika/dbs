@extends('layouts.setting-form')

@push('header')
    Edit Penguji {{ $examregistration->student->name }} ({{ $examregistration->student->username }})
@endpush

@push('body')
<form id="formAction" action="{{ route('examregistrations.examscores.update',[$examregistration,$examscore]) }}" method="post">
    @csrf
    @method('PUT')

    <div class="card-body">
        <input type="hidden" name="oldexaminer_id" value="{{ $examscore->user_id }}">
        {{-- penguji lama --}}
        <div class="row mb-3">
            <label for="examiner_id" class="col-md-4 col-form-label text-md-end">Penguji Lama</label>
            <div class="col-md-8">
                <input id="examiner_id" type="tetxt" value="{{ $examscore->lecture->initial }} - {{ $examscore->lecture->name }}" class="form-control" name="examiner_id" disabled>
            </div>
        </div>
        {{-- penguji baru --}}
        <div class="row mb-3">
            <label for="newexaminer_id" class="col-md-4 col-form-label text-md-end">Pengganti</label>
            <div class="col-md-8">
                <select id="newexaminer_id" class="form-control @error('newexaminer_id') is-invalid @enderror" name="newexaminer_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}">{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- submit Button --}}
        <div class="row mb-0">
            <div class="col-md-8 offset-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <a href="{{ route('examregistrations.examscores.index',$examregistration) }}" class="btn btn-outline-secondary btn-sm">Close</a>
            </div>
        </div>
    </div>
</form>
@endpush
