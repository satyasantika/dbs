@extends('layouts.app')

@section('content')
<div class="container">
    <div class="col-md-10 mx-auto">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-header">Usulan NUIR Masuk — Menunggu Respons</div>
            <div class="card-body">
                @forelse ($pending as $proposal)
                    <div class="border rounded p-2 mb-2">
                        <strong>{{ $proposal->submission->user->name }}</strong> — {{ $proposal->submission->title }}
                        <a href="{{ route('nuir.dosen.show', $proposal) }}" class="btn btn-sm btn-primary float-end">Detail</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">Tidak ada usulan menunggu respons.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">Sudah Direspons</div>
            <div class="card-body">
                @forelse ($responded as $proposal)
                    <div class="border rounded p-2 mb-2">
                        <strong>{{ $proposal->submission->user->name }}</strong> —
                        {{ $proposal->guide1->name }} / {{ $proposal->guide2->name }}
                        <a href="{{ route('nuir.dosen.show', $proposal) }}" class="btn btn-sm btn-outline-secondary float-end">Detail</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada riwayat respons.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
