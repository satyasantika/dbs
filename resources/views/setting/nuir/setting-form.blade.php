@extends('layouts.setting-form')

@push('header')
    {{ $nuirSetting->id ? 'Edit' : 'Tambah' }} Konfigurasi NUIR
    @if ($nuirSetting->id)
        <form id="delete-form" action="{{ route('nuir-settings.destroy', $nuirSetting->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus setting angkatan {{ $nuirSetting->year_generation }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form action="{{ $nuirSetting->id ? route('nuir-settings.update', $nuirSetting->id) : route('nuir-settings.store') }}" method="post">
    @csrf
    @if ($nuirSetting->id)
        @method('PUT')
    @endif

    <div class="row mb-3">
        <label for="year_generation" class="col-md-4 col-form-label text-md-end">Angkatan</label>
        <div class="col-md-6">
            <input id="year_generation" type="text" class="form-control @error('year_generation') is-invalid @enderror" name="year_generation" value="{{ old('year_generation', $nuirSetting->year_generation) }}" required>
            @error('year_generation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-3">
        <label for="stage" class="col-md-4 col-form-label text-md-end">Tahap</label>
        <div class="col-md-6">
            <select id="stage" class="form-control @error('stage') is-invalid @enderror" name="stage" required>
                @foreach ([1 => '1 - NUIR penuh', 2 => '2 - Judul saja', 3 => '3 - Tanpa NUIR'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('stage', $nuirSetting->stage) == $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('stage')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-md-4 col-form-label text-md-end">Aktif</label>
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked(old('active', $nuirSetting->active))>
                <label class="form-check-label" for="active">Angkatan aktif untuk pengajuan NUIR</label>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <label for="deadline" class="col-md-4 col-form-label text-md-end">Deadline</label>
        <div class="col-md-6">
            <input id="deadline" type="date" class="form-control @error('deadline') is-invalid @enderror" name="deadline" value="{{ old('deadline', optional($nuirSetting->deadline)->format('Y-m-d')) }}">
            @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-3">
        <label for="min_references_approved" class="col-md-4 col-form-label text-md-end">Min referensi disetujui</label>
        <div class="col-md-6">
            <input id="min_references_approved" type="number" min="1" max="20" class="form-control @error('min_references_approved') is-invalid @enderror" name="min_references_approved" value="{{ old('min_references_approved', $nuirSetting->min_references_approved ?? 10) }}" required>
            @error('min_references_approved')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    @foreach (['max_chars_novelty' => 'Novelty', 'max_chars_urgency' => 'Urgency', 'max_chars_impact' => 'Impact'] as $field => $label)
        <div class="row mb-3">
            <label for="{{ $field }}" class="col-md-4 col-form-label text-md-end">Max karakter {{ $label }}</label>
            <div class="col-md-6">
                <input id="{{ $field }}" type="number" min="100" class="form-control @error($field) is-invalid @enderror" name="{{ $field }}" value="{{ old($field, $nuirSetting->{$field}) }}" placeholder="kosongkan = tidak dibatasi">
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    @endforeach

    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('nuir-settings.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
