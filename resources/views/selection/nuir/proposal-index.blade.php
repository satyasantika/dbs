@extends('layouts.app')

@section('content')
<div class="container">
    <div class="col-md-10 mx-auto">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        @if ($finalProposal)
            <div class="alert alert-success">
                Pembimbing sudah ditetapkan: {{ $finalProposal->guide1->name }} &amp; {{ $finalProposal->guide2->name }}
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Usulan Calon Pembimbing NUIR</span>
                @if (!$finalProposal && $contentOkSubmission)
                    @can('create nuir proposal')
                        <a href="{{ route('nuir.proposal.create') }}" class="btn btn-primary btn-sm">Buat Usulan</a>
                    @endcan
                @endif
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Dosen 1</th><th>Status 1</th><th>Catatan 1</th>
                            <th>Dosen 2</th><th>Status 2</th><th>Catatan 2</th><th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proposals as $proposal)
                            <tr>
                                <td>{{ $proposal->guide1->name }}</td>
                                <td><span class="badge bg-secondary">{{ $proposal->guide1_status }}</span></td>
                                <td>{{ $proposal->guide1_note }}</td>
                                <td>{{ $proposal->guide2->name }}</td>
                                <td><span class="badge bg-secondary">{{ $proposal->guide2_status }}</span></td>
                                <td>{{ $proposal->guide2_note }}</td>
                                <td>{{ $proposal->created_at?->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Belum ada usulan calon pembimbing.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
