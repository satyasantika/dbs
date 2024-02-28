@extends('layouts.setting-form')

@push('header')
    {{ $guideexaminer->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($guideexaminer->id)
        <form id="delete-form" action="{{ route('guideexaminers.destroy',$guideexaminer->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $guideexaminer->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $guideexaminer->id ? route('guideexaminers.update',$guideexaminer->id) : route('guideexaminers.store') }}" method="post">
    @csrf
    @if ($guideexaminer->id)
        @method('PUT')
    @endif

    <div class="card-body">
        {{-- mahasiswa --}}
        <div class="row mb-3">
            <label for="user_id" class="col-md-4 col-form-label text-md-end">Mahasiswa</label>
            <div class="col-md-8">
                <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required @disabled($guideexaminer->id)>
                    <option value="">-- Pilih Mahasiswa --</option>
                    @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected($student->id == $guideexaminer->user_id)>{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- tahun --}}
        <div class="row mb-3">
            <label for="year_generation" class="col-md-4 col-form-label text-md-end">Tahun</label>
            <div class="col-md-8">
                <select id="year_generation" class="form-control @error('year_generation') is-invalid @enderror" name="year_generation" required @disabled($guideexaminer->id)>
                    <option value="">-- Pilih Tahun --</option>
                    @foreach ([2017,2018,2019,2020,2021,2022,2023] as $year_generation)
                    <option value="{{ $year_generation }}" @selected($guideexaminer->year_generation == $year_generation)>{{ $year_generation }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 1 --}}
        <div class="row mb-3">
            <label for="examiner1_id" class="col-md-4 col-form-label text-md-end">Penguji 1</label>
            <div class="col-md-8">
                <select id="examiner1_id" class="form-control @error('examiner1_id') is-invalid @enderror" name="examiner1_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $guideexaminer->examiner1_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 2 --}}
        <div class="row mb-3">
            <label for="examiner2_id" class="col-md-4 col-form-label text-md-end">Penguji 2</label>
            <div class="col-md-8">
                <select id="examiner2_id" class="form-control @error('examiner2_id') is-invalid @enderror" name="examiner2_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $guideexaminer->examiner2_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 3 --}}
        <div class="row mb-3">
            <label for="examiner3_id" class="col-md-4 col-form-label text-md-end">Penguji 3</label>
            <div class="col-md-8">
                <select id="examiner3_id" class="form-control @error('examiner3_id') is-invalid @enderror" name="examiner3_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $guideexaminer->examiner3_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 4 --}}
        <div class="row mb-3">
            <label for="guide1_id" class="col-md-4 col-form-label text-md-end">Penguji 4 (P1)</label>
            <div class="col-md-8">
                <select id="guide1_id" class="form-control @error('guide1_id') is-invalid @enderror" name="guide1_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $guideexaminer->guide1_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- penguji 5 --}}
        <div class="row mb-3">
            <label for="guide2_id" class="col-md-4 col-form-label text-md-end">Penguji 5 (P2)</label>
            <div class="col-md-8">
                <select id="guide2_id" class="form-control @error('guide2_id') is-invalid @enderror" name="guide2_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $guideexaminer->guide2_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Proposal Date --}}
        <div class="row mb-3">
            <label for="proposal_date" class="col-md-4 col-form-label text-md-end">Tanggal Proposal</label>
            <div class="col-md-8">
                <input type="date" placeholder="proposal_date" value="{{ $guideexaminer->proposal_date ? $guideexaminer->proposal_date->format('Y-m-d') : "" }}" name="proposal_date" class="form-control" id="proposal_date">
            </div>
        </div>
        {{-- seminar Date --}}
        <div class="row mb-3">
            <label for="seminar_date" class="col-md-4 col-form-label text-md-end">Tanggal Seminar Hasil</label>
            <div class="col-md-8">
                <input type="date" placeholder="seminar_date" value="{{ $guideexaminer->seminar_date ? $guideexaminer->seminar_date->format('Y-m-d') : "" }}" name="seminar_date" class="form-control" id="seminar_date">
            </div>
        </div>
        {{-- thesis Date --}}
        <div class="row mb-3">
            <label for="thesis_date" class="col-md-4 col-form-label text-md-end">Tanggal Sidang Skripsi</label>
            <div class="col-md-8">
                <input type="date" placeholder="thesis_date" value="{{ $guideexaminer->thesis_date ? $guideexaminer->thesis_date->format('Y-m-d') : "" }}" name="thesis_date" class="form-control" id="thesis_date">
            </div>
        </div>

        {{-- ketua penguji --}}
        @if ($guideexaminer->id)
        <div class="row mb-3">
            <label for="chief_id" class="col-md-4 col-form-label text-md-end">Ketua Penguji</label>
            <div class="col-md-8">
                <select id="chief_id" class="form-control @error('chief_id') is-invalid @enderror" name="chief_id" required >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($chiefs as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == $guideexaminer->chief_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif

        {{-- submit Button --}}
        <div class="row mb-0">
            <div class="col-md-8 offset-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <a href="{{ route('guideexaminers.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
            </div>
        </div>
    </div>
</form>
@endpush
