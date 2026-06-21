@extends('errors.layout')

@section('title', 'Kesalahan Server')
@section('code', '500')
@section('accent', '#ef4444')
@section('accent-soft', 'rgba(239,68,68,.18)')

@section('heading', 'Kesalahan Server')
@section('description', 'Terjadi kesalahan internal pada server saat memproses permintaan Anda. Tim pengelola sistem telah diberitahu. Silakan coba lagi nanti atau kembali ke halaman utama.')

@if(config('app.debug') && filled($exception->getMessage()))
    @section('detail-label', 'Pesan Debug')
    @section('detail')
        {{ $exception->getMessage() }}
    @endsection
@endif

@section('meta')
    <span class="error-tag">Status: Internal Server Error</span>
    @if(config('app.debug'))
        <span class="error-tag">Mode: Debug aktif</span>
    @endif
@endsection
