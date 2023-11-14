@extends('layouts.setting-form')

@push('header')
    {{ $examregistration->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($examregistration->id)
        <form id="delete-form" action="{{ route('examregistrations.destroy',$examregistration->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $examregistration->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $examregistration->id ? route('examregistrations.update',$examregistration->id) : route('examregistrations.store') }}" method="post">
    @csrf
    @if ($examregistration->id)
        @method('PUT')
    @endif

    <div class="card-body">
        {{-- mahasiswa --}}
        <div class="row mb-3">
            <label for="user_id" class="col-md-4 col-form-label text-md-end">Mahasiswa</label>
            <div class="col-md-6">
                <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required @disabled($examregistration->id)>
                    <option value="">-- Pilih Mahasiswa --</option>
                    @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected($student->id == $examregistration->user_id)>{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- jenis ujian --}}
        <div class="row mb-3">
            <label for="exam_type_id" class="col-md-4 col-form-label text-md-end">Jenis Ujian</label>
            <div class="col-md-6">
                <select id="exam_type_id" class="form-control @error('exam_type_id') is-invalid @enderror" name="exam_type_id" required @disabled($examregistration->id)>
                    <option value="">-- Pilih Ujian --</option>
                    @foreach ($exam_types as $exam_type)
                    <option value="{{ $exam_type->id }}" @selected($exam_type->id == $examregistration->exam_type_id)>{{ $exam_type->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- ujian ke- --}}
        <div class="row mb-3">
            <label for="registration_order" class="col-md-4 col-form-label text-md-end">Ujian Ke-</label>
            <div class="col-md-6">
                <select id="registration_order" class="form-control @error('registration_order') is-invalid @enderror" name="registration_order" required >
                    <option value="">-- Ujian ke- --</option>
                    @foreach ([1,2,3] as $registration_order)
                    <option value="{{ $registration_order }}" @selected($examregistration->registration_order == $registration_order)>{{ $registration_order }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @if ($examregistration->id)
        {{-- penguji 1 --}}
        <div class="row mb-3">
            <label for="examiner1_id" class="col-md-4 col-form-label text-md-end">Penguji 1</label>
            <div class="col-md-6">
                <select id="examiner1_id" class="form-control @error('examiner1_id') is-invalid @enderror" name="examiner1_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->examiner1_id)>{{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 2 --}}
        <div class="row mb-3">
            <label for="examiner2_id" class="col-md-4 col-form-label text-md-end">Penguji 2</label>
            <div class="col-md-6">
                <select id="examiner2_id" class="form-control @error('examiner2_id') is-invalid @enderror" name="examiner2_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->examiner2_id)>{{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 3 --}}
        <div class="row mb-3">
            <label for="examiner3_id" class="col-md-4 col-form-label text-md-end">Penguji 3</label>
            <div class="col-md-6">
                <select id="examiner3_id" class="form-control @error('examiner3_id') is-invalid @enderror" name="examiner3_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->examiner3_id)>{{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 4 --}}
        <div class="row mb-3">
            <label for="guide1_id" class="col-md-4 col-form-label text-md-end">Penguji 4 (P1)</label>
            <div class="col-md-6">
                <select id="guide1_id" class="form-control @error('guide1_id') is-invalid @enderror" name="guide1_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->guide1_id)>{{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 5 --}}
        <div class="row mb-3">
            <label for="guide2_id" class="col-md-4 col-form-label text-md-end">Penguji 5 (P2)</label>
            <div class="col-md-6">
                <select id="guide2_id" class="form-control @error('guide2_id') is-invalid @enderror" name="guide2_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->guide2_id)>{{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- ketua penguji --}}
        <div class="row mb-3">
            <label for="chief" class="col-md-4 col-form-label text-md-end">Penguji 5 (P2)</label>
            <div class="col-md-6">
                <select id="chief" class="form-control @error('chief') is-invalid @enderror" name="chief" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->chief)>{{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

        {{-- Exam Date --}}
        <div class="row mb-3">
            <label for="exam_date" class="col-md-4 col-form-label text-md-end">Tanggal Ujian</label>
            <div class="col-md-6">
                <input type="date" placeholder="exam_date" value="{{ $examregistration->exam_date ? $examregistration->exam_date->format('Y-m-d') : "" }}" name="exam_date" class="form-control" id="exam_date">
            </div>
        </div>
        {{-- Exam Time --}}
        <div class="row mb-3">
            <label for="exam_time" class="col-md-4 col-form-label text-md-end">Pukul Ujian</label>
            <div class="col-md-6">
                <input type="time" placeholder="exam_time" value="{{ $examregistration->exam_time }}" name="exam_time" class="form-control" id="exam_time">
            </div>
        </div>

        {{-- submit Button --}}
        <div class="row mb-0">
            <div class="col-md-8 offset-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <a href="{{ route('examregistrations.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
            </div>
        </div>
    </div>
</form>
    @if ($examregistration->id && \App\Models\ExamScore::where('exam_registration_id',$examregistration->id)->doesntExist())
        <form id="scoreset-form" action="{{ route('examregistrations.scoreset',$examregistration->id) }}" method="POST">
            @csrf
            @method('PUT')
            <button type="submit" class="btn btn-outline-success btn-sm float-end" onclick="return confirm('Yakin akan set ujian?');">
                {{ __('Set Ujian') }}
            </button>
        </form>
    @endif

@endpush
