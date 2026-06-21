@extends('errors.layout')

@section('title', 'Layanan Tidak Tersedia')
@section('code', '503')
@section('accent', '#94a3b8')
@section('accent-soft', 'rgba(148,163,184,.18)')

@section('heading', 'Layanan Tidak Tersedia')
@section('description', 'Sistem sedang dalam pemeliharaan atau sementara tidak dapat diakses. Silakan coba kembali beberapa saat lagi. Terima kasih atas kesabaran Anda.')

@if(filled($exception->getMessage()) && $exception->getMessage() !== 'Service Unavailable')
    @section('detail-label', 'Informasi')
    @section('detail')
        {{ $exception->getMessage() }}
    @endsection
@endif

@section('meta')
    <span class="error-tag">Status: Maintenance / Unavailable</span>
@endsection
