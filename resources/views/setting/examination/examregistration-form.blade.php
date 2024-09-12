@extends('layouts.general')

@push('header')
    | {{ $examregistration->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
    @if ($examregistration->id && \App\Models\ExamScore::where('exam_registration_id',$examregistration->id)->doesntExist())
        <form id="delete-form" action="{{ route('examregistrations.destroy',$examregistration->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $examregistration->name }}?');">
                {{ __('delete') }}
            </button>
        </form>
    @endif
@endpush

@push('body')
<form id="formAction" action="{{ $examregistration->id ? route('examregistrations.update',$examregistration->id) : route('examregistrations.store') }}" method="post">
    @csrf
    @if ($examregistration->id)
        @method('PUT')
    @endif

    <div class="card-body">
        {{-- mahasiswa --}}
        <div class="row mb-3">
            <label for="user_id" class="col-md-4 col-form-label text-md-end">Mahasiswa</label>
            <div class="col-md-7">
                <select id="user_id" class="form-control @error('user_id') is-invalid @enderror" name="user_id" required @disabled($examregistration->id)>
                    <option value="">-- Pilih Mahasiswa --</option>
                    @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected($student->id == $examregistration->user_id)>{{ $student->name }} - {{ $student->username }}</option>
                    @endforeach
                </select>
            </div>
        </div>


    <div class="row mb-3">
        <div class="col-md-2"></div>
        <div class="col-md-9">
        {{-- opsi tambahan --}}
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingZero">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseZero" aria-expanded="true" aria-controls="collapseZero">
                        Identitas Ujian
                    </button>
                </h2>
                <div id="collapseZero" class="accordion-collapse collapse show" aria-labelledby="headingZero" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        {{-- jenis ujian --}}
                        <div class="row mb-3">
                            <label for="exam_type_id" class="col-md-4 col-form-label text-md-end">Jenis Ujian</label>
                            <div class="col-md-7">
                                <select id="exam_type_id" class="form-control @error('exam_type_id') is-invalid @enderror" name="exam_type_id" required>
                                    <option value="">-- Pilih Ujian --</option>
                                    @foreach ($exam_types as $exam_type)
                                    <option value="{{ $exam_type->id }}" @selected($exam_type->id == $examregistration->exam_type_id)>{{ $exam_type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- ujian ke- --}}
                        <div class="row mb-3">
                            <label for="registration_order" class="col-md-4 col-form-label text-md-end">Ujian Ke-</label>
                            <div class="col-md-2">
                                <select id="registration_order" class="form-control @error('registration_order') is-invalid @enderror" name="registration_order" required >
                                    <option value="">pilih ...</option>
                                    @foreach ([1,2,3] as $registration_order)
                                    <option value="{{ $registration_order }}" @selected($examregistration->registration_order == $registration_order)>{{ $registration_order }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- Exam Date --}}
                        <div class="row mb-3">
                            <label for="exam_date" class="col-md-4 col-form-label text-md-end">Tanggal Ujian</label>
                            <div class="col-md-7">
                                <input type="date" placeholder="exam_date" value="{{ $examregistration->exam_date ? $examregistration->exam_date->format('Y-m-d') : "" }}" name="exam_date" class="form-control" id="exam_date">
                            </div>
                        </div>
                        {{-- Exam Time --}}
                        <div class="row mb-3">
                            <label for="exam_time" class="col-md-4 col-form-label text-md-end">Pukul Ujian</label>
                            <div class="col-md-7">
                                <input type="time" placeholder="exam_time" value="{{ $examregistration->exam_time }}" name="exam_time" class="form-control" id="exam_time">
                            </div>
                        </div>
                        {{-- room --}}
                        <div class="row mb-3">
                            <label for="room" class="col-md-4 col-form-label text-md-end">Tempat Ujian</label>
                            <div class="col-md-7">
                                <select id="room" class="form-control @error('room') is-invalid @enderror" name="room">
                                    <option value="">-- Pilih Ruang Ujian --</option>
                                    @foreach ([1,2,3,4] as $room)
                                    <option value="{{ $room }}" @selected($examregistration->room == $room)>Ruang Sidang {{ $room }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- judul penelitian --}}
                        <div class="row mb-3">
                            <label for="title" class="col-md-4 col-form-label text-md-end">Judul Penelitian</label>
                            <div class="col-md-7">
                                <textarea name="title" rows="5" class="form-control" id="title" placeholder="">{{ $examregistration->title }}</textarea>
                            </div>
                        </div>
                        {{-- ipk --}}
                        <div class="row mb-3">
                            <label for="ipk" class="col-md-4 col-form-label text-md-end">IPK</label>
                            <div class="col-md-2">
                                <input
                                    type="number"
                                    value="{{ $examregistration->ipk }}"
                                    name="ipk"
                                    class="form-control"
                                    id="ipk"
                                    min="2.00"
                                    max="4.00"
                                    step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    @if ($examregistration->id)
            <div class="accordion-item">
                {{-- opsi penguji --}}
                <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                    Para Penguji
                </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        {{-- penguji 1 --}}
                        <div class="row mb-3">
                            <label for="examiner1_id" class="col-md-4 col-form-label text-md-end">Penguji 1</label>
                            <div class="col-md-7">
                                <select id="examiner1_id" class="form-control @error('examiner1_id') is-invalid @enderror" name="examiner1_id" required @disabled($exam_score_set)>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($lectures as $lecture)
                                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->examiner1_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- penguji 2 --}}
                        <div class="row mb-3">
                            <label for="examiner2_id" class="col-md-4 col-form-label text-md-end">Penguji 2</label>
                            <div class="col-md-7">
                                <select id="examiner2_id" class="form-control @error('examiner2_id') is-invalid @enderror" name="examiner2_id" required @disabled($exam_score_set)>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($lectures as $lecture)
                                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->examiner2_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- penguji 3 --}}
                        <div class="row mb-3">
                            <label for="examiner3_id" class="col-md-4 col-form-label text-md-end">Penguji 3</label>
                            <div class="col-md-7">
                                <select id="examiner3_id" class="form-control @error('examiner3_id') is-invalid @enderror" name="examiner3_id" required @disabled($exam_score_set)>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($lectures as $lecture)
                                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->examiner3_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- penguji 4 --}}
                        <div class="row mb-3">
                            <label for="guide1_id" class="col-md-4 col-form-label text-md-end">Penguji 4 (P1)</label>
                            <div class="col-md-7">
                                <select id="guide1_id" class="form-control @error('guide1_id') is-invalid @enderror" name="guide1_id" required @disabled($exam_score_set)>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($lectures as $lecture)
                                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->guide1_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- penguji 5 --}}
                        <div class="row mb-3">
                            <label for="guide2_id" class="col-md-4 col-form-label text-md-end">Penguji 5 (P2)</label>
                            <div class="col-md-7">
                                <select id="guide2_id" class="form-control @error('guide2_id') is-invalid @enderror" name="guide2_id" required @disabled($exam_score_set)>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($lectures as $lecture)
                                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->guide2_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- ketua penguji --}}
                        <div class="row mb-3">
                            <label for="chief_id" class="col-md-4 col-form-label text-md-end">Ketua Penguji</label>
                            <div class="col-md-7">
                                <select id="chief_id" class="form-control @error('chief_id') is-invalid @enderror" name="chief_id" >
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($chiefs as $lecture)
                                    <option value="{{ $lecture->id }}" @selected($lecture->id == $examregistration->chief_id)>{{ $lecture->initial }} - {{ $lecture->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                {{-- opsi link jadwal --}}
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        Opsi Link Jadwal Ujian
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        {{-- link jadwal --}}
                        <div class="row mb-3">
                            <label for="schedule_link" class="col-md-4 col-form-label text-md-end">Link Jadwal</label>
                            <div class="col-md-7">
                                <textarea name="schedule_link" rows="3" class="form-control" id="schedule_link" placeholder="">{{ $examregistration->schedule_link }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                {{-- opsi online --}}
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        Opsi Online
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        {{-- Link Ujian --}}
                        <div class="row mb-3">
                            <label for="online_link" class="col-md-4 col-form-label text-md-end">Link Ujian</label>
                            <div class="col-md-7">
                                <textarea name="online_link" rows="5" class="form-control" id="online_link" placeholder="">{{ $examregistration->online_link }}</textarea>
                            </div>
                        </div>
                        {{-- online_user --}}
                        <div class="row mb-3">
                            <label for="online_user" class="col-md-4 col-form-label text-md-end">user</label>
                            <div class="col-md-2">
                                <input
                                    type="number"
                                    value="{{ $examregistration->online_user }}"
                                    name="online_user"
                                    class="form-control"
                                    id="online_user"
                                    min="0"
                                    max="4"
                                    step="0.01">
                            </div>
                        </div>
                        {{-- online_password --}}
                        <div class="row mb-3">
                            <label for="online_password" class="col-md-4 col-form-label text-md-end">password</label>
                            <div class="col-md-7">
                                <input
                                    type="text"
                                    value="{{ $examregistration->online_password }}"
                                    name="online_password"
                                    class="form-control"
                                    id="online_password"
                                    min="0"
                                    max="4"
                                    step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    </div>


        {{-- submit Button --}}
        <div class="row mt-3">
            <div class="col-md-8 offset-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <a href="{{ route('examregistrations.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
            </div>
        </div>
    </div>
</form>

{{-- tombol set ujian --}}
@if ($examregistration->id && \App\Models\ExamScore::where('exam_registration_id',$examregistration->id)->doesntExist())
    <form id="scoreset-form" action="{{ route('examregistrations.scoreset',$examregistration->id) }}" method="POST">
        @csrf
        @method('PUT')
        <button type="submit" class="btn btn-outline-success btn-sm float-end" onclick="return confirm('Yakin akan set ujian?');">
            {{ __('Set Ujian') }}
        </button>
    </form>
@endif

@endpush
