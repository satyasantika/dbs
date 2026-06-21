@extends('errors.layout')

@section('title', 'Akses Ditolak')
@section('code', '403')
@section('accent', '#f87171')
@section('accent-soft', 'rgba(248,113,113,.18)')

@section('heading', 'Akses Ditolak')
@section('description', 'Anda tidak memiliki izin untuk mengakses halaman ini atau melakukan tindakan yang diminta. Pastikan akun Anda memiliki peran dan hak akses yang sesuai.')

@php
    $detailMessage = $exception->getMessage();
    $defaultMessages = ['Forbidden', 'This action is unauthorized.', ''];
@endphp

@if(filled($detailMessage) && ! in_array($detailMessage, $defaultMessages, true))
    @section('detail-label', 'Alasan')
    @section('detail')
        {{ $detailMessage }}
    @endsection
@endif

@section('meta')
    <span class="error-tag">Status: Tidak diizinkan</span>
    @auth
        <span class="error-tag">Akun: {{ auth()->user()->username ?? auth()->user()->name }}</span>
    @endauth
@endsection
