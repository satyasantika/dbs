@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card">
                <div class="card-header">Review NUIR — {{ $nuirSubmission->user->name }}</div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    <p><strong>Angkatan:</strong> {{ $nuirSubmission->year_generation }} |
                        <strong>Versi:</strong> {{ $nuirSubmission->version }} |
                        <strong>Status:</strong> {{ $nuirSubmission->status }}</p>

                    @if ($history->isNotEmpty())
                        <h6>Riwayat Versi</h6>
                        @foreach ($history as $old)
                            <div class="border rounded p-2 mb-2">
                                v{{ $old->version }} — {{ $old->status }}
                                @if ($old->dbs_note)
                                    <div class="small text-muted">{{ $old->dbs_note }}</div>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    <h6>Konten</h6>
                    <p><strong>Judul:</strong> {{ $nuirSubmission->title }}</p>
                    <p><strong>Novelty:</strong><br>{!! nl2br(e($nuirSubmission->novelty)) !!}</p>
                    <p><strong>Urgency:</strong><br>{!! nl2br(e($nuirSubmission->urgency)) !!}</p>
                    <p><strong>Impact:</strong><br>{!! nl2br(e($nuirSubmission->impact)) !!}</p>

                    <p>{{ $approvedCount }} dari {{ $nuirSubmission->references->count() }} referensi disetujui.
                        Standar minimum: {{ $setting?->min_references_approved ?? 10 }}.</p>

                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Indexer</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($nuirSubmission->references as $ref)
                                    <tr>
                                        <td>{{ $ref->ref_order }}</td>
                                        <td>{{ $ref->indexer_name }}</td>
                                        <td>
                                            @if ($ref->ref_approved === true)
                                                <span class="badge bg-success">Disetujui</span>
                                            @elseif ($ref->ref_approved === false)
                                                <span class="badge bg-danger">Ditolak</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $ref->ref_note }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('nuir.review.reference', $ref) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="ref_approved" value="1">
                                                <button class="btn btn-success btn-sm">✓</button>
                                            </form>
                                            <form method="POST" action="{{ route('nuir.review.reference', $ref) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="ref_approved" value="0">
                                                <input type="text" name="ref_note" placeholder="Alasan penolakan" class="form-control form-control-sm d-inline-block w-auto">
                                                <button class="btn btn-danger btn-sm">✗</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('nuir.review.submit', $nuirSubmission) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Catatan DBS</label>
                            <textarea name="dbs_note" class="form-control" rows="3">{{ old('dbs_note', $nuirSubmission->dbs_note) }}</textarea>
                            @error('dbs_note')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" name="action" value="content_ok" class="btn btn-success btn-sm"
                            @disabled($approvedCount < ($setting?->min_references_approved ?? 10))>
                            Setujui Konten
                        </button>
                        <button type="submit" name="action" value="revision" class="btn btn-warning btn-sm">Minta Revisi</button>
                        <a href="{{ route('nuir.review.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
