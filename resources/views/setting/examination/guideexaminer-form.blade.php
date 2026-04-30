@extends('layouts.setting-form')

@push('header')
    {{ $guideexaminer->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($guideexaminer->id && is_null($guideexaminer->proposal_date) && is_null($guideexaminer->seminar_date) && is_null($guideexaminer->thesis_date))
        <form id="delete-form" action="{{ route('guideexaminers.destroy',$guideexaminer->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $guideexaminer->student->name ?? '' }}?');">
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
                @if ($guideexaminer->id)
                    <input type="text" class="form-control" value="{{ $guideexaminer->student->name ?? '-' }}" disabled>
                @else
                    <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required>
                        <option value="">-- Pilih Mahasiswa --</option>
                        @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>{{ $student->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>
        </div>
        {{-- tahun --}}
        <div class="row mb-3">
            <label for="year_generation" class="col-md-4 col-form-label text-md-end">Tahun</label>
            <div class="col-md-8">
                @if ($guideexaminer->id)
                    <input type="text" class="form-control" value="{{ $guideexaminer->year_generation }}" disabled>
                @else
                    <select id="year_generation" class="form-control @error('year_generation') is-invalid @enderror" name="year_generation" required>
                        <option value="">-- Pilih Tahun --</option>
                        @foreach ([2017,2018,2019,2020,2021,2022,2023,2024,2025] as $year)
                        <option value="{{ $year }}" @selected(old('year_generation', $guideexaminer->year_generation) == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                    @error('year_generation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>
        </div>
        {{-- penguji 1 --}}
        <div class="row mb-3">
            <label for="examiner1_id" class="col-md-4 col-form-label text-md-end">Penguji 1</label>
            <div class="col-md-8">
                <select id="examiner1_id" class="form-control @error('examiner1_id') is-invalid @enderror" name="examiner1_id" required>
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == old('examiner1_id', $guideexaminer->examiner1_id))>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
                @error('examiner1_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        {{-- penguji 2 --}}
        <div class="row mb-3">
            <label for="examiner2_id" class="col-md-4 col-form-label text-md-end">Penguji 2</label>
            <div class="col-md-8">
                <select id="examiner2_id" class="form-control @error('examiner2_id') is-invalid @enderror" name="examiner2_id" required>
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == old('examiner2_id', $guideexaminer->examiner2_id))>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
                @error('examiner2_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        {{-- penguji 3 --}}
        <div class="row mb-3">
            <label for="examiner3_id" class="col-md-4 col-form-label text-md-end">Penguji 3</label>
            <div class="col-md-8">
                <select id="examiner3_id" class="form-control @error('examiner3_id') is-invalid @enderror" name="examiner3_id" required>
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == old('examiner3_id', $guideexaminer->examiner3_id))>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
                @error('examiner3_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        {{-- penguji 4 --}}
        <div class="row mb-3">
            <label for="guide1_id" class="col-md-4 col-form-label text-md-end">Penguji 4 (P1)</label>
            <div class="col-md-8">
                <select id="guide1_id" class="form-control @error('guide1_id') is-invalid @enderror" name="guide1_id" required>
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == old('guide1_id', $guideexaminer->guide1_id))>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
                @error('guide1_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        {{-- penguji 5 --}}
        <div class="row mb-3">
            <label for="guide2_id" class="col-md-4 col-form-label text-md-end">Penguji 5 (P2)</label>
            <div class="col-md-8">
                <select id="guide2_id" class="form-control @error('guide2_id') is-invalid @enderror" name="guide2_id" required>
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($lectures as $lecture)
                    <option value="{{ $lecture->id }}" @selected($lecture->id == old('guide2_id', $guideexaminer->guide2_id))>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                    @endforeach
                </select>
                @error('guide2_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Proposal Date --}}
        <div class="row mb-3">
            <label for="proposal_date" class="col-md-4 col-form-label text-md-end">Tanggal Proposal</label>
            <div class="col-md-8">
                <input type="date" name="proposal_date" class="form-control @error('proposal_date') is-invalid @enderror" id="proposal_date"
                    value="{{ old('proposal_date', $guideexaminer->proposal_date?->format('Y-m-d')) }}">
                @error('proposal_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        {{-- seminar Date --}}
        <div class="row mb-3">
            <label for="seminar_date" class="col-md-4 col-form-label text-md-end">Tanggal Seminar Hasil</label>
            <div class="col-md-8">
                <input type="date" name="seminar_date" class="form-control @error('seminar_date') is-invalid @enderror" id="seminar_date"
                    value="{{ old('seminar_date', $guideexaminer->seminar_date?->format('Y-m-d')) }}">
                @error('seminar_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        {{-- thesis Date --}}
        <div class="row mb-3">
            <label for="thesis_date" class="col-md-4 col-form-label text-md-end">Tanggal Sidang Skripsi</label>
            <div class="col-md-8">
                <input type="date" name="thesis_date" class="form-control @error('thesis_date') is-invalid @enderror" id="thesis_date"
                    value="{{ old('thesis_date', $guideexaminer->thesis_date?->format('Y-m-d')) }}">
                @error('thesis_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

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
