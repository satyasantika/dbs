@extends('layouts.app')

@section('content')
<div class="container">
    <div class="col-md-10 mx-auto card">
        <div class="card-header">Detail Usulan NUIR</div>
        <div class="card-body">
            <p><strong>Mahasiswa:</strong> {{ $proposal->submission->user->name }}</p>
            <p><strong>Judul:</strong> {{ $proposal->submission->title }}</p>
            <p><strong>Novelty:</strong><br>{!! nl2br(e($proposal->submission->novelty)) !!}</p>
            <p><strong>Urgency:</strong><br>{!! nl2br(e($proposal->submission->urgency)) !!}</p>
            <p><strong>Impact:</strong><br>{!! nl2br(e($proposal->submission->impact)) !!}</p>

            <h6>Referensi</h6>
            <table class="table table-sm">
                <thead><tr><th>#</th><th>Indexer</th><th>Status DBS</th><th>Catatan</th></tr></thead>
                <tbody>
                    @foreach ($proposal->submission->references as $ref)
                        <tr>
                            <td>{{ $ref->ref_order }}</td>
                            <td>{{ $ref->indexer_name }}</td>
                            <td>
                                @if ($ref->ref_approved === true) Disetujui
                                @elseif ($ref->ref_approved === false) Ditolak
                                @else Pending @endif
                            </td>
                            <td>{{ $ref->ref_note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($canRespond)
                <form method="POST" action="{{ route('nuir.dosen.accept', $proposal) }}" class="d-inline">
                    @csrf
                    @method('PUT')
                    <button class="btn btn-success btn-sm">Terima</button>
                </form>
                <form method="POST" action="{{ route('nuir.dosen.reject', $proposal) }}" class="d-inline">
                    @csrf
                    @method('PUT')
                    <input type="text" name="note" class="form-control form-control-sm d-inline-block w-auto" placeholder="Alasan penolakan" required>
                    <button class="btn btn-danger btn-sm">Tolak</button>
                </form>
            @endif

            <a href="{{ route('nuir.dosen.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
</div>
@endsection
