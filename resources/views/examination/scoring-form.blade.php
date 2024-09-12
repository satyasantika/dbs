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
{{-- jika tanggal ujian sudah lewat satu hari atau semua penguji sudah menilai, maka penilaian diblok --}}
{{-- dikecualikan bagi user yang dapat memaksa edit penilaian --}}
@php
    $available_check = ($examregistration->exam_date < Carbon\Carbon::now() && $examregistration->pass_exam) && !Auth::user()->can('force edit score');
@endphp
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
                    {{ $item->name }} <br>
                </div>
                <div class="col-auto">
                    <select
                        class="form-select mb-3"
                        aria-label=".form-select-lg example"
                        name="{{ $item_order }}"
                        id="{{ $item_order }}"
                        onchange="gradeout.value=grade();letterout.value=letter(grade());{{ $item_order }}out.value={{ $item_order }}.value"
                        @disabled($available_check)
                        >
                        <option value="">Nilai ?</option>
                        <option value="{{ rand(85,100) }}" @selected(intval($scoring->$item_order)>=85 && intval($scoring->$item_order)<=100)>A</option>
                        <option value="{{ rand(77,84) }}" @selected(intval($scoring->$item_order)>=77 && intval($scoring->$item_order)<=84)>A-</option>
                        <option value="{{ rand(69,76) }}" @selected(intval($scoring->$item_order)>=69 && intval($scoring->$item_order)<=76)>B+</option>
                        <option value="{{ rand(61,68) }}" @selected(intval($scoring->$item_order)>=61 && intval($scoring->$item_order)<=68)>B</option>
                        <option value="{{ rand(53,60) }}" @selected(intval($scoring->$item_order)>=53 && intval($scoring->$item_order)<=60)>B-</option>
                        <option value="{{ rand(45,52) }}" @selected(intval($scoring->$item_order)>=45 && intval($scoring->$item_order)<=52)>C+</option>
                        <option value="{{ rand(37,44) }}" @selected(intval($scoring->$item_order)>=37 && intval($scoring->$item_order)<=44)>C</option>
                        <option value="{{ rand(29,36) }}" @selected(intval($scoring->$item_order)>=29 && intval($scoring->$item_order)<=36)>C-</option>
                        <option value="{{ rand(21,28) }}" @selected(intval($scoring->$item_order)>=21 && intval($scoring->$item_order)<=28)>D</option>
                        <option value="{{ rand(0,20) }}" @selected(intval($scoring->$item_order)>=0 && intval($scoring->$item_order)<=20)>E</option>
                    </select>
                </div>
                {{-- <div class="col-auto">
                    <output
                        class="btn btn-outline-dark disabled"
                        id="{{ $item_order }}out"
                        name="{{ $item_order }}out"
                        for="allout"
                        >{{ $scoring->$item_order }}</output>
                </div> --}}
            </div>
            @endforeach
            <hr>
            {{-- angka dan huruf hasil penilaian --}}
            <div class="row alert alert-success">
                <div class="col-auto">SIMPULAN HASIL AKHIR PENILAIAN</div>
                @php
                    $grade = ($scoring->score1 + $scoring->score2 + $scoring->score3 + $scoring->score4 + $scoring->score5)/5;
                @endphp
                <div class="col-auto">
                    <output
                        class="btn btn-success disabled"
                        id="letterout"
                        name="letterout"
                        for="allout"
                        >@if ($grade<21) E @elseif ($grade<29) D @elseif ($grade<37) C-
                                @elseif ($grade<45) C @elseif ($grade<53) C+ @elseif ($grade<61) B-
                                @elseif ($grade<69) B @elseif ($grade<77) B+ @elseif ($grade<85) A-
                                @elseif ($grade<=100) A @endif</output>
                </div>
                <div class="col-auto">
                    <output
                        class="btn btn-outline-dark disabled"
                        id="gradeout"
                        name="gradeout"
                        for="allout"
                        >{{ $grade }}</output>
                    </div>
            </div>
            <hr>
            {{-- revisi/tidak --}}
            <div class="row">
                <div class="col-auto text-end">Perlu direvisi?</div>
                <div class="col-auto">
                    <input type="radio" class="btn-check" name="revision" id="revisi1" autocomplete="off" @checked($scoring->revision==1) value=1 @disabled($available_check) onClick='document.getElementById("revision_row").style.display = "block" '>
                    <label class="btn btn-outline-danger btn-sm float-end" for="revisi1">YA</label>
                </div>
                <div class="col-auto">
                    <input type="radio" class="btn-check" name="revision" id="revisi2" autocomplete="off" @checked($scoring->revision==0) value=0 @disabled($available_check) onClick='document.getElementById("revision_row").style.display = "none" '>
                    <label class="btn btn-outline-success btn-sm" for="revisi2">TIDAK</label>
                </div>
            </div>
            <hr>
            <div class="row" id="revision_row" @style( $scoring->revision==1 ? "display:block" : "display:none" ) >
                <div class="col">
                    {{-- Komentar --}}
                    <div class="row mb-3">
                        <label for="revision_note" class="form-label">Catatan Revisi <br>(jika dibiarkan kosong, maka akan tercetak <span @class('text-primary')>belum diisi</span> pada keterangan lembar revisi mahasiswa)</label>
                        <div class="col-md-12">
                            <textarea name="revision_note" rows="10" class="form-control" id="revision_note" @disabled($available_check)>{{ $scoring->revision_note }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $judgement = $scoring->registration->exam_type_id == 3 ? 'LULUS' : 'LANJUT'
            @endphp
            <div class="row">
                <div class="col-auto text-end">Layak di_{{ $judgement }}_kan?</div>
                <div class="col-auto">
                    <input type="radio" class="btn-check" name="pass_approved" id="approved1" autocomplete="off" @checked($scoring->pass_approved==1) value=1 @disabled($available_check)>
                    <label class="btn btn-outline-success btn-sm float-end" for="approved1">YA, {{ $judgement }}</label>
                </div>
                <div class="col-auto">
                    <input type="radio" class="btn-check" name="pass_approved" id="approved2" autocomplete="off" @checked($scoring->pass_approved==0) value=0 @disabled($available_check)>
                    <label class="btn btn-outline-danger btn-sm" for="approved2">TIDAK {{ $judgement }}</label>
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
            <button type="submit" class="btn btn-primary btn-sm m-1" @disabled($available_check)>Save</button>
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
            } else if (grade<29) {
                return "D";
            } else if (grade<37) {
                return "C-";
            } else if (grade<45) {
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
