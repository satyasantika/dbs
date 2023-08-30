@extends('layouts.setting-form')

@push('header')
    {{ $user->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($user->id)
        <form id="delete-form" action="{{ route('users.destroy',$user->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $user->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $user->id ? route('users.update',$user->id) : route('users.store') }}" method="post">
    @csrf
    @if ($user->id)
        @method('PUT')
    @endif
    <div class="card-body">
        {{-- Nama Lengkap --}}
        <div class="row mb-3">
            <label for="name" class="col-md-4 col-form-label text-md-end">Nama Lengkap</label>
            <div class="col-md-6">
                <input type="text" placeholder="Nama Lengkap (bergelar - bila ada)" value="{{ $user->name }}" name="name" class="form-control" id="name" required autofocus>
            </div>
        </div>
        {{-- Username --}}
        <div class="row mb-3">
            <label for="username" class="col-md-4 col-form-label text-md-end">Username</label>
            <div class="col-md-6">
                <input type="text" placeholder="username" value="{{ $user->username }}" name="username" class="form-control" id="username" required>
            </div>
        </div>
        {{-- Email --}}
        <div class="row mb-3">
            <label for="email" class="col-md-4 col-form-label text-md-end">Alamat Email</label>
            <div class="col-md-6">
                <input type="email" placeholder="email" value="{{ $user->email }}" name="email" class="form-control" id="email" required>
            </div>
        </div>
        {{-- Password --}}
        <div class="row mb-3">
            <label for="password" class="col-md-4 col-form-label text-md-end">Password</label>
            <div class="col-md-6">
                @if ($user->id)
                    {{-- TODO - Reset Password --}}
                    <a class="btn btn-warning" href="#"
                        onclick="event.preventDefault();
                                    if (confirm('yakin direset?')){
                                        document.getElementById('passwordreset-form').submit();
                                    }
                                    ">
                        {{ __('Reset') }}
                    </a>
                    <form id="passwordreset-form" action="{{ route('password.reset',$user->id) }}" method="POST" class="d-none">
                        @csrf
                    </form>
                    <input type="hidden" placeholder="password" value="{{ $user->password }}" name="password" class="form-control" id="password" required>
                @else
                    <input type="password" placeholder="password" value="{{ $user->password }}" name="password" class="form-control" id="password" required>
                @endif
            </div>
        </div>
        {{-- Role --}}
        <div class="row mb-3">
            <label for="role" class="col-md-4 col-form-label text-md-end">Tetapkan Role</label>
            <div class="col-md-6">
                <select id="role" class="form-control @error('role') is-invalid @enderror" name="role" required {{ $user->id ? 'disabled' : '' }}>
                    @if ($user->id)
                    <option value="">{{ $user->getRoleNames()->implode(', ') }}</option>
                    @else
                    <option value="">-- Tanpa Role --</option>
                    @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ $role == $user->getRoleNames()->implode(', ') ? 'selected' : '' }}>{{ $role }}</option>
                    @endforeach
                    @endif
                </select>
            </div>
        </div>
        {{-- Gender --}}
        <div class="row mb-3">
            <label for="gender" class="col-md-4 col-form-label text-md-end">Jenis Kelamin</label>
            <div class="col-md-6">
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
        {{-- Birth Place --}}
        <div class="row mb-3">
            <label for="birth_place" class="col-md-4 col-form-label text-md-end">Tempat Lahir</label>
            <div class="col-md-6">
                <input type="text" placeholder="birth_place" value="{{ $user->birth_place }}" name="birth_place" class="form-control" id="birth_place">
            </div>
        </div>
        {{-- Birth Date --}}
        <div class="row mb-3">
            <label for="birth_date" class="col-md-4 col-form-label text-md-end">Tanggal Lahir</label>
            <div class="col-md-6">
                <input type="date" placeholder="birth_date" value="{{ $user->birth_date ? $user->birth_date->format('Y-m-d') : date('Y-m-d') }}" name="birth_date" class="form-control" id="birth_date">
            </div>
        </div>
        {{-- submit Button --}}
        <div class="row mb-0">
            <div class="col-md-8 offset-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
            </div>
        </div>
    </div>
</form>
@endpush
