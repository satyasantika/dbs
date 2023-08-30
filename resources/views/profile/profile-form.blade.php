@extends('layouts.setting-form')

@push('header')
    Edit Profilku
@endpush

@push('body')
<form id="formAction" action="{{ route('profiles.update',$user->id) }}" method="post">
    @csrf
    @method('PUT')

    <div class="card-body">
        {{-- Nama Lengkap --}}
        <div class="row mb-3">
            <label for="name" class="col-md-4 col-form-label text-md-end">Nama Lengkap</label>
            <div class="col-md-8">
                <input type="text" placeholder="Nama Lengkap (bergelar - bila ada)" value="{{ $user->name }}" name="name" class="form-control" id="name" required autofocus>
            </div>
        </div>
        {{-- Email --}}
        <div class="row mb-3">
            <label for="email" class="col-md-4 col-form-label text-md-end">Alamat Email</label>
            <div class="col-md-8">
                <input type="email" placeholder="email" value="{{ $user->email }}" name="email" class="form-control" id="email" required>
            </div>
        </div>
        {{-- Gender --}}
        <div class="row mb-3">
            <label for="gender" class="col-md-4 col-form-label text-md-end">Jenis Kelamin</label>
            <div class="col-md-8">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gender" id="genderL" value="L" {{ $user->gender == 'L' ? 'checked' : '' }}>
                    <label class="form-check-label" for="genderL">Laki-laki</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gender" id="genderP" value="P" {{ $user->gender == 'P' ? 'checked' : '' }}>
                    <label class="form-check-label" for="genderP">Perempuan</label>
                </div>
            </div>
        </div>
        @hasanyrole('mahasiswa')
        {{-- Birth Place --}}
        <div class="row mb-3">
            <label for="birth_place" class="col-md-4 col-form-label text-md-end">Tempat Lahir</label>
            <div class="col-md-8">
                <input type="text" placeholder="birth_place" value="{{ $user->birth_place }}" name="birth_place" class="form-control" id="birth_place">
            </div>
        </div>
        {{-- Birth Date --}}
        <div class="row mb-3">
            <label for="birth_date" class="col-md-4 col-form-label text-md-end">Tanggal Lahir</label>
            <div class="col-md-8">
                <input type="date" placeholder="birth_date" value="{{ $user->birth_date ? $user->birth_date->format('Y-m-d') : date('Y-m-d') }}" name="birth_date" class="form-control" id="birth_date">
            </div>
        </div>
        @endhasanyrole
        {{-- Address --}}
        <div class="row mb-3">
            <label for="address" class="col-md-4 col-form-label text-md-end">Alamat Jelas</label>
            <div class="col-md-8">
                <textarea name="address" rows="5" class="form-control" id="address">{{ $user->address }}</textarea>
            </div>
        </div>
        {{-- Phone --}}
        <div class="row mb-3">
            <label for="phone" class="col-md-4 col-form-label text-md-end">no. WA aktif</label>
            <div class="col-md-8">
                <input type="text" placeholder="phone" value="{{ $user->phone }}" name="phone" class="form-control" id="phone" required>
            </div>
        </div>
        {{-- submit Button --}}
        <div class="row mb-0">
            <div class="col-md-8 offset-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <a href="{{ route('profiles.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
            </div>
        </div>
    </div>
</form>
@endpush
