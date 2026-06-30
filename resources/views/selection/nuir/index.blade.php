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

                    @if (session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif
                    @if (session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    @if ($stage3 ?? false)
                        <p>Angkatan Anda tidak memerlukan pengajuan NUIR. Pembimbing akan ditetapkan langsung oleh DBS.</p>
                    @elseif ($closed ?? false)
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

                        @if ($submission->status === 'revision')
                            <div class="alert alert-warning">
                                <strong>Diminta Revisi</strong>
                                <div>{{ $submission->dbs_note }}</div>
                            </div>
                            @php($rejected = $submission->references()->where('ref_approved', false)->get())
                            @if ($rejected->isNotEmpty())
                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>#</th><th>Catatan DBS</th></tr></thead>
                                    <tbody>
                                        @foreach ($rejected as $ref)
                                            <tr><td>{{ $ref->ref_order }}</td><td>{{ $ref->ref_note }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                            @if (!\App\Models\NuirSubmission::where('parent_submission_id', $submission->id)->exists())
                                <a href="{{ route('nuir.submission.revise', $submission) }}" class="btn btn-warning btn-sm">
                                    Buat Revisi (v{{ $submission->version + 1 }})
                                </a>
                            @endif
                        @endif

                        <div class="mb-3">
                            @if ($submission->isEditable())
                                <a href="{{ route('nuir.submission.edit', $submission) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                            @elseif ($submission->isReferencesEditable())
                                <a href="{{ route('nuir.submission.edit', $submission) }}" class="btn btn-outline-primary btn-sm">Kelola Referensi</a>
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
