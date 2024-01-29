@extends('layouts.general')

@push('header')
<br>Penilaian {{ $scoring->registration->examtype->name }}
@if (auth()->user()->hasRole('admin'))
<a href="{{ route('examregistrations.examscores.index',$scoring->exam_registration_id) }}" class="btn btn-outline-primary btn-sm float-end">kembali</a>
@else
<a href="{{ route('scoring.index') }}" class="btn btn-outline-primary btn-sm float-end">kembali</a>
@endif

@endpush

@push('body')
<strong>{{ $scoring->registration->student->username }}</strong><br>
<strong>{{ $scoring->registration->student->name }}</strong><br>
Judul {{ $scoring->registration->exam_type_id == 1 ? 'Proposal' : 'Skripsi' }}: <strong>{{ $scoring->registration->title }}</strong>
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
                    <li>pada masing-masing aspek penilaian, silakan pilih salah satu penilaian yang tersedia</li>
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
                $item_order = 'score'.$order;
                $options = [];
            @endphp
            <div class="row">
                <div class="col-auto">
                    <span class="badge bg-light text-dark">nomor {{ $order }}</span>
                </div>
                <div class="col-6">
                    {{-- <div class="mb-3"> --}}
                        {{ $item->name }} <br>
                        {{-- <div class="row">

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
                        </div> --}}
                    {{-- </div> --}}
                </div>
                <div class="col-auto">
                    <select
                        class="form-select mb-3"
                        aria-label=".form-select-lg example"
                        name="{{ $item_order }}"
                        id="{{ $item_order }}"
                        onchange="gradeout.value=grade();letterout.value=letter(grade());"
                        >
                        <option value="">Nilai ?</option>
                        @for ($i = 100; $i >= 0; $i--)
                            <option value="{{ $i }}" @selected($scoring->$item_order == $i)>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                </div>
            @endforeach
            <hr>
            HASIL PENILAIAN:
            <div class="row">
                @php
                    $grade = ($scoring->score1 + $scoring->score2 + $scoring->score3 + $scoring->score4 + $scoring->score5)/5;
                @endphp
                <div class="col-auto">
                    <output
                        class="btn btn-outline-dark disabled"
                        id="gradeout"
                        name="gradeout"
                        for="allout"
                        >{{ $grade }}</output>
                    </div>
                <div class="col-auto">
                    <output
                        class="btn btn-outline-dark disabled"
                        id="letterout"
                        name="letterout"
                        for="allout"
                        >@if ($grade<21) E @elseif ($grade<29) D @elseif ($grade<37) C-
                                @elseif ($grade<45) C @elseif ($grade<53) C+ @elseif ($grade<61) B-
                                @elseif ($grade<69) B @elseif ($grade<77) B+ @elseif ($grade<85) A-
                                @elseif ($grade<=100) A @endif</output>
                </div>
            </div>
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
            @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('examregistrations.examscores.index',$scoring->exam_registration_id) }}" class="btn btn-outline-secondary btn-sm float-end">Close</a>
            @else
            <a href="{{ route('scoring.index') }}" class="btn btn-outline-secondary btn-sm float-end">Close</a>
            @endif
            <button type="submit" class="btn btn-primary btn-sm m-1">Save</button>
        </div>
    </form>
</form>
@endpush

@push('scripts')
<script type="text/javascript">
    let grade = () => {
        return (Number(score1.value) + Number(score2.value) + Number(score3.value) + Number(score4.value) + Number(score5.value))/5;
    };
    function letter(grade){
        if (grade<21) {
                return "E";
            } else if (grade<37) {
                return "D";
            } else if (grade<45) {
                return "C-";
            } else if (grade<53) {
                return "C";
            } else if (grade<53) {
                return "C+";
            } else if (grade<61) {
                return "B-";
            } else if (grade<69) {
                return "B";
            } else if (grade<77) {
                return "B+";
            } else if (grade<85) {
                return "A-";
            } else {
                return "A";
            }
    };
</script>
@endpush
