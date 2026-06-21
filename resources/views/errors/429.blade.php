@extends('errors.layout')

@section('title', 'Terlalu Banyak Permintaan')
@section('code', '429')
@section('accent', '#f472b6')
@section('accent-soft', 'rgba(244,114,182,.18)')

@section('heading', 'Terlalu Banyak Permintaan')
@section('description', 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu beberapa saat sebelum mencoba lagi agar sistem tetap stabil untuk semua pengguna.')

@section('meta')
    <span class="error-tag">Status: Rate limit tercapai</span>
@endsection
