@extends('layouts.app')

@section('content')
<div class="container">
    <div class="col-md-10 mx-auto card">
        <div class="card-header">Detail Usulan NUIR</div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            <p><strong>Mahasiswa:</strong> {{ $proposal->submission->user->name }}</p>
            <p><strong>Status NUIR:</strong> {{ $proposal->submission->status }}</p>
            <p><strong>Judul:</strong> {{ $proposal->submission->title }}</p>
            <p><strong>Novelty:</strong><br>{!! nl2br(e($proposal->submission->novelty)) !!}</p>
            <p><strong>Urgency:</strong><br>{!! nl2br(e($proposal->submission->urgency)) !!}</p>
            <p><strong>Impact:</strong><br>{!! nl2br(e($proposal->submission->impact)) !!}</p>

            @if ($canReviewReferences)
                <h6 class="mt-4">Review Konten NUIR (Novelty, Urgency, Impact)</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Aspek</th>
                            <th>Review Anda</th>
                            <th>Catatan Anda</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['novelty' => 'Novelty', 'urgency' => 'Urgency', 'impact' => 'Impact'] as $field => $label)
                            @php($contentReview = $proposal->submission->contentReviewFor(auth()->user(), $proposal, $field))
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    @if ($contentReview?->approved === true) Disetujui
                                    @elseif ($contentReview?->approved === false) Ditolak
                                    @else Pending @endif
                                </td>
                                <td>{{ $contentReview?->note }}</td>
                                <td>
                                    <form method="POST" action="{{ route('nuir.dosen.review-content', $proposal) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="field" value="{{ $field }}">
                                        <input type="hidden" name="approved" value="1">
                                        <button class="btn btn-success btn-sm">Setujui</button>
                                    </form>
                                    <form method="POST" action="{{ route('nuir.dosen.review-content', $proposal) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="field" value="{{ $field }}">
                                        <input type="hidden" name="approved" value="0">
                                        <input type="text" name="note" class="form-control form-control-sm d-inline-block w-auto" placeholder="Alasan penolakan" required>
                                        <button class="btn btn-danger btn-sm">Tolak</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($proposal->submission->status === 'revision' && $proposal->submission->dbs_note)
                <div class="alert alert-warning">
                    <strong>Permintaan Revisi NUIR</strong>
                    <div>{{ $proposal->submission->dbs_note }}</div>
                </div>
            @endif

            <h6>Referensi</h6>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Indexer</th>
                        <th>Status Validator</th>
                        <th>Catatan Validator</th>
                        <th>Review Anda</th>
                        <th>Catatan Anda</th>
                        @if ($canReviewReferences)
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proposal->submission->references as $ref)
                        @php($guideReview = $ref->guideReviewFor(auth()->user(), $proposal))
                        <tr>
                            <td>{{ $ref->ref_order }}</td>
                            <td>{{ $ref->indexer_name }}</td>
                            <td>
                                @if ($ref->ref_approved === true) Disetujui
                                @elseif ($ref->ref_approved === false) Ditolak
                                @else Pending @endif
                            </td>
                            <td>{{ $ref->ref_note }}</td>
                            <td>
                                @if ($guideReview?->approved === true) Disetujui
                                @elseif ($guideReview?->approved === false) Ditolak
                                @else Pending @endif
                            </td>
                            <td>{{ $guideReview?->note }}</td>
                            @if ($canReviewReferences)
                                <td>
                                    <form method="POST" action="{{ route('nuir.dosen.review-reference', [$proposal, $ref]) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="approved" value="1">
                                        <button class="btn btn-success btn-sm">Setujui</button>
                                    </form>
                                    <form method="POST" action="{{ route('nuir.dosen.review-reference', [$proposal, $ref]) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="approved" value="0">
                                        <input type="text" name="note" class="form-control form-control-sm d-inline-block w-auto" placeholder="Alasan penolakan" required>
                                        <button class="btn btn-danger btn-sm">Tolak</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($canRespond)
                @if ($canAcceptProposal)
                    <form method="POST" action="{{ route('nuir.dosen.accept', $proposal) }}" class="d-inline">
                        @csrf
                        @method('PUT')
                        <button class="btn btn-success btn-sm">Terima Menjadi Pembimbing</button>
                    </form>
                @else
                    <p class="text-muted small">
                        Persetujuan menjadi pembimbing baru tersedia setelah NUIR disetujui final (content_ok).
                        Anda tetap dapat mereview referensi dan melihat permintaan revisi.
                    </p>
                @endif
                <form method="POST" action="{{ route('nuir.dosen.reject', $proposal) }}" class="d-inline">
                    @csrf
                    @method('PUT')
                    <input type="text" name="note" class="form-control form-control-sm d-inline-block w-auto" placeholder="Alasan penolakan usulan" required>
                    <button class="btn btn-danger btn-sm">Tolak Usulan</button>
                </form>
            @endif

            <a href="{{ route('nuir.dosen.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
</div>
@endsection
