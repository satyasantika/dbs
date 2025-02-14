@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Change Password') }}</div>

                <div class="card-body">
                    {{-- ALERT PASSWORD CHANGE --}}
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors)
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger">{{ $error }}</div>
                        @endforeach
                    @endif

                    <form method="POST" action="{{ route('mypassword.change') }}">
                        @csrf

                        <div class="row mb-3{{ $errors->has('current-password') ? ' has-error' : '' }}">
                            <label for="current-password" class="col-md-4 col-form-label text-md-end">{{ __('Password saat ini') }}</label>

                            <div class="col-md-6">
                                <input id="current-password" type="password" class="form-control @error('current-password') is-invalid @enderror" name="current-password" value="{{ old('current-password') }}" required autofocus>

                                @error('current-password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3{{ $errors->has('new-password') ? ' has-error' : '' }}">
                            <label for="new-password" class="col-md-4 col-form-label text-md-end">{{ __('Password Baru') }}</label>

                            <div class="col-md-6">
                                <input id="new-password" type="password" class="form-control @error('new-password') is-invalid @enderror" name="new-password" value="{{ old('new-password') }}" required autofocus>

                                @error('new-password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3{{ $errors->has('new-password_confirmation') ? ' has-error' : '' }}">
                            <label for="new-password_confirmation" class="col-md-4 col-form-label text-md-end">{{ __('Konfirmasi Password Baru') }}</label>

                            <div class="col-md-6">
                                <input id="new-password_confirmation" type="password" class="form-control @error('new-password_confirmation') is-invalid @enderror" name="new-password_confirmation" value="{{ old('new-password_confirmation') }}" required autofocus>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Ubah Password') }}
                                </button>
                                <a href="{{ route('home') }}" class="btn btn-secondary float-end">Kembali</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
