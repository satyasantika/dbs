@extends('errors.layout')

@section('title', 'Kesalahan Server')
@section('code', $exception->getStatusCode())
@section('accent', '#ef4444')
@section('accent-soft', 'rgba(239,68,68,.18)')

@section('heading', 'Kesalahan Server')
@section('description', 'Terjadi masalah pada server saat memproses permintaan Anda. Silakan coba lagi nanti atau hubungi administrator jika masalah berlanjut.')

@if(config('app.debug') && filled($exception->getMessage()))
    @section('detail-label', 'Pesan Debug')
    @section('detail')
        {{ $exception->getMessage() }}
    @endsection
@endif

@section('meta')
    <span class="error-tag">Status: Server Error</span>
@endsection
