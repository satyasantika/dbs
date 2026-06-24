@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ $submission->id ? 'Edit' : 'Buat' }} Pengajuan NUIR</div>
                <div class="card-body">
                    <p><strong>Mahasiswa:</strong> {{ auth()->user()->name }} ({{ auth()->user()->username }})</p>

                    <form method="POST" action="{{ $submission->id ? route('nuir.submission.update', $submission) : route('nuir.submission.store') }}">
                        @csrf
                        @if ($submission->id)
                            @method('PUT')
                        @endif

                        @foreach (['title' => 'Judul', 'novelty' => 'Novelty', 'urgency' => 'Urgency', 'impact' => 'Impact'] as $field => $label)
                            <div class="mb-3">
                                <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                                <textarea id="{{ $field }}" name="{{ $field }}" rows="{{ $field === 'title' ? 2 : 4 }}" class="form-control @error($field) is-invalid @enderror" required>{{ old($field, $submission->{$field}) }}</textarea>
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach

                        <h6>Referensi (1-10)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Link OJS</th>
                                        <th>Indexer</th>
                                        <th>Link Index</th>
                                        <th>Link Drive</th>
                                        <th>Kutipan</th>
                                        <th>Relevansi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($indexers = ['WoS', 'Scopus', 'Thomson', 'Elsevier', 'Springer', 'Wiley', 'Taylor&Francis', 'DOAJ', 'Sinta 2'])
                                    @for ($i = 1; $i <= 10; $i++)
                                        @php($ref = $submission->references->firstWhere('ref_order', $i))
                                        <tr>
                                            <td>{{ $i }}</td>
                                            <td><input type="text" class="form-control form-control-sm" name="references[{{ $i }}][link_ojs]" value="{{ old("references.$i.link_ojs", $ref->link_ojs ?? '') }}"></td>
                                            <td>
                                                <select class="form-control form-control-sm" name="references[{{ $i }}][indexer_name]">
                                                    <option value="">--</option>
                                                    @foreach ($indexers as $indexer)
                                                        <option value="{{ $indexer }}" @selected(old("references.$i.indexer_name", $ref->indexer_name ?? '') === $indexer)>{{ $indexer }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control form-control-sm" name="references[{{ $i }}][link_index]" value="{{ old("references.$i.link_index", $ref->link_index ?? '') }}"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="references[{{ $i }}][link_drive]" value="{{ old("references.$i.link_drive", $ref->link_drive ?? '') }}"></td>
                                            <td><textarea class="form-control form-control-sm" name="references[{{ $i }}][quote]" rows="2">{{ old("references.$i.quote", $ref->quote ?? '') }}</textarea></td>
                                            <td><textarea class="form-control form-control-sm" name="references[{{ $i }}][relevance]" rows="2">{{ old("references.$i.relevance", $ref->relevance ?? '') }}</textarea></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">Simpan Draft</button>
                        <a href="{{ route('nuir.submission.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
