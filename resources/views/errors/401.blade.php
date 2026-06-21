@extends('errors.layout')

@section('title', 'Autentikasi Diperlukan')
@section('code', '401')
@section('accent', '#fbbf24')
@section('accent-soft', 'rgba(251,191,36,.18)')

@section('heading', 'Autentikasi Diperlukan')
@section('description', 'Anda perlu masuk ke akun terlebih dahulu sebelum dapat mengakses halaman ini. Silakan login menggunakan username dan password yang terdaftar.')

@section('meta')
    <span class="error-tag">Status: Tidak terautentikasi</span>
@endsection

@section('extra-actions')
    <a href="{{ route('login') }}" class="btn-error btn-secondary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Masuk
    </a>
@endsection
