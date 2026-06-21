@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('code', '404')
@section('accent', '#60a5fa')
@section('accent-soft', 'rgba(96,165,250,.18)')

@section('heading', 'Halaman Tidak Ditemukan')
@section('description', 'Halaman yang Anda cari tidak ada, mungkin sudah dipindahkan, dihapus, atau URL yang dimasukkan salah. Periksa kembali alamat URL atau gunakan navigasi untuk kembali.')

@section('detail-label', 'URL yang diminta')
@section('detail')
    {{ request()->fullUrl() }}
@endsection

@section('meta')
    <span class="error-tag">Metode: {{ request()->method() }}</span>
@endsection
