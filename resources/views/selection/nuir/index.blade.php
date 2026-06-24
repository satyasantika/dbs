@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">Pengajuan NUIR</div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($closed ?? false)
                        <p class="text-muted">NUIR belum dibuka untuk angkatan Anda.</p>
                    @elseif (!$submission)
                        <p>Anda belum memiliki pengajuan NUIR aktif.</p>
                        @can('create nuir submission')
                            <a href="{{ route('nuir.submission.create') }}" class="btn btn-primary btn-sm">Buat Pengajuan NUIR</a>
                        @endcan
                    @else
                        <p>
                            Status:
                            <span class="badge bg-secondary">{{ $submission->status }}</span>
                        </p>
                        <p><strong>Judul:</strong> {{ $submission->title }}</p>

                        @if ($submission->dbs_note)
                            <div class="alert alert-warning">{{ $submission->dbs_note }}</div>
                        @endif

                        <div class="mb-3">
                            @if ($submission->isEditable())
                                <a href="{{ route('nuir.submission.edit', $submission) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                            @endif
                            @if ($submission->status === 'draft')
                                <form action="{{ route('nuir.submission.submit', $submission) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-primary btn-sm">Kirim ke DBS</button>
                                </form>
                            @endif
                        </div>

                        @if ($versions->count() > 1)
                            <h6>Riwayat Versi</h6>
                            <ul class="list-group">
                                @foreach ($versions as $version)
                                    <li class="list-group-item">
                                        v{{ $version->version }} — {{ $version->status }}
                                        @if ($version->dbs_note)
                                            <small class="text-muted">({{ $version->dbs_note }})</small>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
