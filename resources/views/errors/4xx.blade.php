@extends('errors.layout')

@section('title', 'Permintaan Tidak Valid')
@section('code', $exception->getStatusCode())
@section('accent', '#fb7185')
@section('accent-soft', 'rgba(251,113,133,.18)')

@section('heading', 'Permintaan Tidak Valid')
@section('description', 'Server tidak dapat memproses permintaan Anda karena terjadi kesalahan pada sisi klien. Periksa URL, metode permintaan, atau data yang dikirim, lalu coba lagi.')

@if(filled($exception->getMessage()))
    @section('detail-label', 'Detail')
    @section('detail')
        {{ $exception->getMessage() }}
    @endsection
@endif

@section('meta')
    <span class="error-tag">Status: Client Error</span>
@endsection
