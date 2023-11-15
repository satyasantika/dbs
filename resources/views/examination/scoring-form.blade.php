@extends('layouts.general')

@push('header')
<br>Penilaian {{ $scoring->registration->examtype->name }}
<a href="{{ route('scoring.index') }}" class="btn btn-outline-primary btn-sm float-end">kembali</a>
@endpush

@push('body')
<strong>{{ $scoring->registration->student->username }}</strong><br>
<strong>{{ $scoring->registration->student->name }}</strong>
<hr>
<div class="text-end">
    Penilai: {{ $scoring->lecture->name }}
</div>
<form id="formAction" action="{{ route('scoring.update',$scoring->id) }}" method="post">
    @csrf
    @method('PUT')
        <div class="modal-body m-3">
            <div class="row alert alert-info">
                <input type="hidden" value="{{ $scoring->exam_registration_id }}" name="exam_registration_id" class="form-control" id="exam_registration_id" >
                Petunjuk:<br>
                <ol>
                    <li>pada masing-masing aspek penilaian, silakan geser titik biru ke kanan dan hingga diperoleh nilai yang diharapkan</li>
                    <li>pastikan memilih perlu direvisi/tidak</li>
                    <li>pastikan mencatat apa yang perlu direvisi</li>
                    <li>pastikan memberikan keputusan apakah dapat dilanjutkan ke tahap berikutnya atau tidak</li>
                </ol>

                <br>

            </div>
            {{-- skor penilaian --}}
            @foreach ($form_items as $item)
            @php
                if ($item->exam_type_id == 3) {
                    $order = ($item->id) - 10;
                } elseif ($item->exam_type_id == 2) {
                    $order = ($item->id) - 5;
                } else {
                    $order = ($item->id);
                }
                $item_order = 'score0'.$order;
                $options = [];
            @endphp
            <div class="row">
                <div class="col-auto">
                    <span class="badge bg-light text-dark">nomor {{ $order }}</span>
                </div>
                <div class="col-10">
                    <div class="mb-3">
                        {{ $item->name }} <br>
                        <div class="row">
                            <div class="col-md-6">
                                <input
                                    type="range"
                                    class="form-range"
                                    id="{{ $item_order }}"
                                    name="{{ $item_order }}"
                                    min="0"
                                    max="100"
                                    step="1"
                                    oninput="{{ $item_order }}out.value={{ $item_order }}.value"
                                    value="{{ $scoring->$item_order ?? 0 }}">
                            </div>
                            <div class="col-md-6">
                                <output
                                    class="btn btn-outline-dark disabled"
                                    id="{{ $item_order }}out"
                                    name="{{ $item_order }}out"
                                    for="{{ $item_order }}"
                                    >{{ $scoring->$item_order ?? 0 }}</output>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <hr>
            <div class="row">
                <div class="col text-end">Keputusan Revisi:</div>
                <div class="col">
                    <input type="radio" class="btn-check" name="revision" id="revisi1" autocomplete="off" @checked($scoring->revision==1) value=1>
                    <label class="btn btn-outline-danger btn-sm float-end" for="revisi1">perlu direvisi</label>
                </div>
                <div class="col">
                    <input type="radio" class="btn-check" name="revision" id="revisi2" autocomplete="off" @checked($scoring->revision==0) value=0>
                    <label class="btn btn-outline-success btn-sm" for="revisi2">tidak perlu direvisi</label>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col">
                    {{-- Komentar --}}
                    <div class="row mb-3">
                        <label for="revision_note" class="form-label">Keterangan Revisi</label>
                        <div class="col-md-12">
                            <textarea name="revision_note" rows="10" class="form-control" id="revision_note" placeholder="jika bagian ini kosong, maka dianggap tidak ada revisi">{{ $scoring->revision_note }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col text-end">Keputusan Ujian:</div>
                <div class="col">
                    <input type="radio" class="btn-check" name="pass_approved" id="approved1" autocomplete="off" @checked($scoring->pass_approved==1) value=1>
                    <label class="btn btn-outline-success btn-sm float-end" for="approved1">layak dilanjutkan</label>
                </div>
                <div class="col">
                    <input type="radio" class="btn-check" name="pass_approved" id="approved2" autocomplete="off" @checked($scoring->pass_approved==0) value=0>
                    <label class="btn btn-outline-danger btn-sm" for="approved2">tidak layak dilanjutkan</label>
                </div>
            </div>
        </div>
        <hr>
        <div class="modal-footer">
            <a href="{{ route('scoring.index') }}" class="btn btn-outline-secondary btn-sm float-end">Close</a>
            <button type="submit" class="btn btn-primary btn-sm m-1">Save</button>
        </div>
    </form>
</form>
@endpush
