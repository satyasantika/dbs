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
                <h6 class="mt-4">Review Konten NUIR (Judul, Novelty, Urgency, Impact)</h6>
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
                        @foreach (['title' => 'Judul', 'novelty' => 'Novelty', 'urgency' => 'Urgency', 'impact' => 'Impact'] as $field => $label)
                            @php($contentReview = $proposal->submission->contentReviewFor(auth()->user(), $proposal, $field))
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    @if ($contentReview?->approved === true) Disetujui
                                    @elseif ($contentReview?->approved === false) Diminta revisi
                                    @else Pending @endif
                                </td>
                                <td>{{ $contentReview?->note }}</td>
                                <td>
                                    @if ($contentReview?->approved === true)
                                        <form method="POST" action="{{ route('nuir.dosen.cancel-content-review', $proposal) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="field" value="{{ $field }}">
                                            <button class="btn btn-outline-secondary btn-sm">Batalkan</button>
                                        </form>
                                    @else
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
                                            <input type="text" name="note" class="form-control form-control-sm d-inline-block w-auto" placeholder="Catatan revisi" required>
                                            <button class="btn btn-warning btn-sm">Minta Revisi</button>
                                        </form>
                                    @endif
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
                                    @if ($guideReview?->approved === true)
                                        <form method="POST" action="{{ route('nuir.dosen.cancel-reference-review', [$proposal, $ref]) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-secondary btn-sm">Batalkan</button>
                                        </form>
                                    @else
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
                                    @endif
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
                        <button class="btn btn-success btn-sm">Konfirmasi Kursi (Semua Elemen Disetujui)</button>
                    </form>
                @else
                    <p class="text-muted small">
                        Kursi Anda diterima otomatis setelah seluruh elemen NUI (Novelty, Urgency, Impact) disetujui
                        dan NUIR berstatus content_ok. Jika ada elemen diminta revisi, kursi tetap menunggu perbaikan mahasiswa.
                    </p>
                @endif
                <form method="POST" action="{{ route('nuir.dosen.reject', $proposal) }}" class="d-inline ms-2">
                    @csrf
                    @method('PUT')
                    <input type="text" name="note" class="form-control form-control-sm d-inline-block w-auto" placeholder="Catatan penolakan usulan NUI" required>
                    <button class="btn btn-danger btn-sm">Tolak Usulan NUI</button>
                </form>
            @endif

            @if ($revisionHistory->isNotEmpty())
                <h6 class="mt-4">Histori Revisi NUIR</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Versi</th>
                            <th>Elemen</th>
                            <th>Catatan</th>
                            <th>Oleh</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($revisionHistory as $event)
                            <tr>
                                <td>v{{ $event->submission_version }}</td>
                                <td>{{ $event->subjectLabel() }}</td>
                                <td>{{ $event->note }}</td>
                                <td>{{ $event->actor?->name ?? '-' }} ({{ $event->actor_role }})</td>
                                <td>{{ $event->recorded_at?->format('d-m-Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($rejectionHistory->isNotEmpty())
                <h6 class="mt-4">Histori Penolakan Usulan</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Versi</th>
                            <th>Posisi</th>
                            <th>Catatan</th>
                            <th>Oleh</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rejectionHistory as $event)
                            <tr>
                                <td>v{{ $event->submission_version }}</td>
                                <td>{{ strtoupper($event->subject) }}</td>
                                <td>{{ $event->note }}</td>
                                <td>{{ $event->actor?->name ?? '-' }}</td>
                                <td>{{ $event->recorded_at?->format('d-m-Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <a href="{{ route('nuir.dosen.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
</div>
@endsection
