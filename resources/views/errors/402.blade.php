@extends('errors.layout')

@section('title', 'Pembayaran Diperlukan')
@section('code', '402')
@section('accent', '#fb923c')
@section('accent-soft', 'rgba(251,146,60,.18)')

@section('heading', 'Pembayaran Diperlukan')
@section('description', 'Akses ke fitur atau layanan ini memerlukan pembayaran atau langganan aktif. Hubungi administrator sistem jika Anda merasa seharusnya memiliki akses.')

@section('meta')
    <span class="error-tag">Status: Pembayaran belum terverifikasi</span>
@endsection
