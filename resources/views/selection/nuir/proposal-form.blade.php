@extends('layouts.app')

@section('content')
<div class="container">
    <div class="col-md-8 mx-auto card">
        <div class="card-header">Usulkan Pembimbing</div>
        <div class="card-body">
            <div class="alert alert-info">
                NUIR: {{ $submission->title }} (v{{ $submission->version }})
                @if ($previousRejected)
                    <div class="mt-2">NUIR Anda sudah diverifikasi (v{{ $submission->version }}). Anda dapat menggunakan NUIR yang sama.</div>
                @endif
            </div>

            <form method="POST" action="{{ route('nuir.proposal.store') }}">
                @csrf
                <input type="hidden" name="nuir_submission_id" value="{{ $submission->id }}">

                <div class="mb-3">
                    <label class="form-label">Pembimbing 1</label>
                    <select name="guide1_id" class="form-control @error('guide1_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Dosen --</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}" @selected(old('guide1_id') == $lecturer->id)>{{ $lecturer->name }}</option>
                        @endforeach
                    </select>
                    @error('guide1_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Pembimbing 2</label>
                    <select name="guide2_id" class="form-control @error('guide2_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Dosen --</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}" @selected(old('guide2_id') == $lecturer->id)>{{ $lecturer->name }}</option>
                        @endforeach
                    </select>
                    @error('guide2_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Kirim Proposal</button>
                <a href="{{ route('nuir.proposal.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
