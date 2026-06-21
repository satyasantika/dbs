@extends('errors.layout')

@section('title', 'Sesi Halaman Kedaluwarsa')
@section('code', '419')
@section('accent', '#c084fc')
@section('accent-soft', 'rgba(192,132,252,.18)')

@section('heading', 'Sesi Halaman Kedaluwarsa')
@section('description', 'Token keamanan formulir (CSRF) sudah tidak valid, biasanya karena halaman dibuka terlalu lama atau sesi browser berakhir. Muat ulang halaman sebelumnya, lalu kirim ulang data Anda.')

@section('meta')
    <span class="error-tag">Penyebab umum: Form idle terlalu lama</span>
@endsection

@section('extra-actions')
    <button type="button" class="btn-error btn-secondary" onclick="window.location.reload()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Muat Ulang
    </button>
@endsection
