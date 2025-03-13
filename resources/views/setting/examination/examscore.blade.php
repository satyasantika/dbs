@extends('layouts.app')
@push('title')
    Penilaian {{ $examregistration->student->name }}
@endpush
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Penilaian Ujian
                    <a href="{{ route('examregistrations.index') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>

                <div class="card-body">
                    {{ $examregistration->student->name }}<br>
                    {{ $examregistration->student->username }}<br>
                    {{ $examregistration->examtype->name }} ({{ $examregistration->exam_date->isoFormat('dddd, D MMMM Y') }} {{ $examregistration->exam_time }})<br>
                    {{ $examregistration->title }}

                    @if (\App\Models\ExamScore::where('exam_registration_id',$examregistration->id)->doesntExist())
                        <div class="alert alert-info mt-3">
                            <form id="scoreset-form" action="{{ route('examregistrations.scoreset',$examregistration->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                jadwal belum diset ke penguji, klik
                                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Yakin akan set ujian?');">
                                    {{ __('Set Ujian') }}
                                </button>
                            </form>
                        </div>
                    @elseif (!$empty_scores)
                        <div class="alert alert-success mt-3">
                            nilai ujian ini sudah lengkap
                        </div>
                    @else
                        <div class="alert alert-danger">
                            penilaian belum lengkap
                        </div>
                    @endif
                    <hr>
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Penguji</th>
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>5</th>
                                    <th>Nilai</th>
                                    <th>Huruf</th>
                                    <th>rev?</th>
                                    <th>catatan</th>
                                    <th>acc?</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exam_scores as $exam_score)
                                <tr class="{{ is_null($exam_score->grade) ? 'table-warning' : 'table-success' }}">
                                    <td>
                                        <a href="{{ route('scoring.edit',$exam_score->id) }}" class="btn btn-sm btn-outline-primary">E</a>
                                    </td>
                                    <td>
                                        <a target="_blank" href="{{'https://api.whatsapp.com/send/?phone=62'
                                            .$exam_score->lecture->phone.'&text=Yth.%20Penguji%20'
                                            .$examregistration->student->name.',%0A%0AMohon%20segera%20memberikan%20penilaian%20'
                                            .$examregistration->examtype->name.'%20pada%20'
                                            .$examregistration->exam_date->isoFormat('dddd, D MMMM Y').'%20agar%20mahasiswa%20tersebut%20dapat%20segera%20mencetak%20lembar%20revisinya%0A%0A'
                                            .'silakan%20akses:%0A%0A'
                                            .route('scoring.edit',['scoring'=>$exam_score])
                                            .'%0A%0A(jika%20eror%20saat%20buka%20link%20di%20handphone,%20pastikan%20awalannya%20http://%20bukan%20https://)'}}"
                                            class="btn btn-sm btn-outline-success"><svg viewBox="0 0 31 30" height="20" preserveAspectRatio="xMidYMid meet" class="x1knego9" fill="none"><title>wa-logo</title><path d="M30.3139 14.3245C30.174 10.4932 28.5594 6.864 25.8073 4.1948C23.0552 1.52559 19.3784 0.0227244 15.5446 4.10118e-06H15.4722C12.8904 -0.00191309 10.3527 0.668375 8.10857 1.94491C5.86449 3.22145 3.99142 5.06026 2.67367 7.28039C1.35592 9.50053 0.6389 12.0255 0.593155 14.6068C0.547411 17.1882 1.17452 19.737 2.41278 22.0024L1.09794 29.8703C1.0958 29.8865 1.09712 29.9029 1.10182 29.9185C1.10651 29.9341 1.11448 29.9485 1.12518 29.9607C1.13588 29.973 1.14907 29.9828 1.16387 29.9896C1.17867 29.9964 1.19475 29.9999 1.21103 30H1.23365L9.01561 28.269C11.0263 29.2344 13.2282 29.7353 15.4586 29.7346C15.6004 29.7346 15.7421 29.7346 15.8838 29.7346C17.8458 29.6786 19.7773 29.2346 21.5667 28.4282C23.3562 27.6218 24.9682 26.469 26.3098 25.0363C27.6514 23.6036 28.696 21.9194 29.3832 20.0809C30.0704 18.2423 30.3867 16.2859 30.3139 14.3245ZM15.8099 27.1487C15.6923 27.1487 15.5747 27.1487 15.4586 27.1487C13.4874 27.1511 11.5444 26.6795 9.79366 25.7735L9.39559 25.5654L4.11815 26.8124L5.09221 21.4732L4.86604 21.0902C3.78579 19.2484 3.20393 17.157 3.17778 15.0219C3.15163 12.8869 3.68208 10.7819 4.71689 8.91419C5.75171 7.0465 7.25518 5.48059 9.07924 4.37067C10.9033 3.26076 12.985 2.64514 15.1194 2.58444C15.238 2.58444 15.3571 2.58444 15.4767 2.58444C18.6992 2.59399 21.7889 3.86908 24.0802 6.13498C26.3715 8.40087 27.681 11.4762 27.7265 14.6984C27.7719 17.9205 26.5498 21.0316 24.3234 23.3612C22.0969 25.6909 19.0444 27.0527 15.8235 27.1532L15.8099 27.1487Z" fill="currentColor"></path><path d="M10.2894 7.69007C10.1057 7.69366 9.92456 7.73407 9.75673 7.80892C9.5889 7.88377 9.43779 7.99154 9.31236 8.12584C8.95801 8.48923 7.96736 9.36377 7.91006 11.2003C7.85277 13.0369 9.13594 14.8538 9.31537 15.1086C9.49481 15.3635 11.7686 19.3306 15.5141 20.9395C17.7156 21.8879 18.6806 22.0507 19.3063 22.0507C19.5642 22.0507 19.7587 22.0236 19.9622 22.0115C20.6483 21.9693 22.1969 21.1762 22.5346 20.3137C22.8724 19.4512 22.895 18.6973 22.806 18.5465C22.7171 18.3957 22.4728 18.2872 22.1049 18.0942C21.737 17.9012 19.9321 16.9361 19.5928 16.8004C19.467 16.7419 19.3316 16.7066 19.1932 16.6964C19.1031 16.7011 19.0155 16.7278 18.938 16.774C18.8605 16.8203 18.7954 16.8847 18.7484 16.9618C18.4469 17.3372 17.7548 18.153 17.5225 18.3882C17.4718 18.4466 17.4093 18.4938 17.3392 18.5265C17.2691 18.5592 17.1928 18.5768 17.1154 18.5782C16.9728 18.5719 16.8333 18.5344 16.7068 18.4681C15.6135 18.0038 14.6167 17.339 13.768 16.5079C12.975 15.7263 12.3022 14.8315 11.7716 13.8526C11.5666 13.4726 11.7716 13.2766 11.9586 13.0987C12.1456 12.9208 12.3461 12.675 12.5391 12.4624C12.6975 12.2808 12.8295 12.0777 12.9312 11.8593C12.9838 11.7578 13.0104 11.6449 13.0085 11.5307C13.0067 11.4165 12.9765 11.3045 12.9206 11.2048C12.8317 11.0149 12.1667 9.14664 11.8546 8.39725C11.6013 7.75642 11.2997 7.73531 11.0358 7.7157C10.8187 7.70062 10.5699 7.69309 10.3211 7.68555H10.2894" fill="currentColor"></path></svg></a>
                                        {{ $exam_score->namadosen }}
                                        @if ($exam_score->dosen == $exam_score->ketua)
                                            <span class="badge rounded-pill bg-dark text-white">ketua</span>
                                        @endif
                                        <a href="{{ route('examregistrations.examscores.edit',[$examregistration,$exam_score]) }}" class="badge rounded-pill bg-primary" style="text-decoration: none;">ganti penguji</a>
                                    </td>
                                    <td>{{ $exam_score->score1 }}</td>
                                    <td>{{ $exam_score->score2 }}</td>
                                    <td>{{ $exam_score->score3 }}</td>
                                    <td>{{ $exam_score->score4 }}</td>
                                    <td>{{ $exam_score->score5 }}</td>
                                    <td class="text-center"><span class="badge bg-dark text-white">{{ $exam_score->grade }}</span></td>
                                    <td class="text-center">{{ $exam_score->letter }}</td>
                                    <td class="text-center">{{ $exam_score->revision ? 'v' : 'x' }}</td>
                                    <td>{{ is_null($exam_score->revision_note) ? 'x' : Str::of($exam_score->revision_note)->limit(20) }}</td>
                                    <td class="text-center">{{ $exam_score->pass_approved ? 'v' : 'x' }}</td>
                                </tr>
                                @empty
                                belum ada data
                                @endforelse
                            </tbody>
                        </table>
                        <a target="_blank" href="{{ route('report.exam-chief',$examregistration->id) }}" class="btn btn-sm btn-success">Hasil Ujian</a>
                        <a target="_blank" href="{{ route('report.revision-table',$examregistration->id) }}" class="btn btn-sm btn-secondary">Lembar Revisi</a>
                        <a target="_blank" href="{{ route('report.revision-sign',$examregistration->id) }}" class="btn btn-sm btn-secondary ">Keterangan Revisi</a>
                    @if (!$empty_scores)
                        <a target="_blank" href="{{'https://api.whatsapp.com/send/?phone=62'
                            .$examregistration->student->phone.'&text=*INFORMASI%20Hasil%20'
                            .$examregistration->examtype->name.'*%0A%0ASaudara%20*'
                            .$examregistration->student->name.'*,%20Kami%20informasikan%20bahwa%20masing-masing%20dosen%20penguji%20telah%20menuliskan%20revisi%20'
                            .$examregistration->examtype->name.'%20('
                            .$examregistration->exam_date->isoFormat('dddd, D MMMM Y').')%20dan%20dapat%20dicetak%20pada%20sistem%20DBS%20berikut.%0A%0A'
                            .route('exam.result')
                            .'%0A(jika%20eror%20saat%20buka%20link%20di%20handphone,%20pastikan%20awalannya%20http://%20bukan%20https://)'
                            .($examregistration->exam_type_id==3 ? '%0A%0ATerakhir,%20harap%20laporkan%20hasil%20ujian%20Anda%20pada%20laman%20(siapkan%20lembar%20revisi%20yang%20sudah%20ditandatangani%20dan%20foto%20ujian):%0A' : '')
                            .($examregistration->exam_type_id==3 ? 'https://forms.gle/umUKgAcXLnhowgpw7' : '')
                            .'%0A%0ADemikian%20informasi%20ini%20Kami%20sampaikan.%20Atas%20perhatian%20Anda,%20Kami%20ucapkan%20terima%20kasih.%0A'
                            .'(ttd.)%20*Kajur%20Pendidikan%20Matematika*'}}"
                            class="btn btn-sm btn-success float-end"><svg viewBox="0 0 31 30" height="20" preserveAspectRatio="xMidYMid meet" class="x1knego9" fill="none"><title>wa-logo</title><path d="M30.3139 14.3245C30.174 10.4932 28.5594 6.864 25.8073 4.1948C23.0552 1.52559 19.3784 0.0227244 15.5446 4.10118e-06H15.4722C12.8904 -0.00191309 10.3527 0.668375 8.10857 1.94491C5.86449 3.22145 3.99142 5.06026 2.67367 7.28039C1.35592 9.50053 0.6389 12.0255 0.593155 14.6068C0.547411 17.1882 1.17452 19.737 2.41278 22.0024L1.09794 29.8703C1.0958 29.8865 1.09712 29.9029 1.10182 29.9185C1.10651 29.9341 1.11448 29.9485 1.12518 29.9607C1.13588 29.973 1.14907 29.9828 1.16387 29.9896C1.17867 29.9964 1.19475 29.9999 1.21103 30H1.23365L9.01561 28.269C11.0263 29.2344 13.2282 29.7353 15.4586 29.7346C15.6004 29.7346 15.7421 29.7346 15.8838 29.7346C17.8458 29.6786 19.7773 29.2346 21.5667 28.4282C23.3562 27.6218 24.9682 26.469 26.3098 25.0363C27.6514 23.6036 28.696 21.9194 29.3832 20.0809C30.0704 18.2423 30.3867 16.2859 30.3139 14.3245ZM15.8099 27.1487C15.6923 27.1487 15.5747 27.1487 15.4586 27.1487C13.4874 27.1511 11.5444 26.6795 9.79366 25.7735L9.39559 25.5654L4.11815 26.8124L5.09221 21.4732L4.86604 21.0902C3.78579 19.2484 3.20393 17.157 3.17778 15.0219C3.15163 12.8869 3.68208 10.7819 4.71689 8.91419C5.75171 7.0465 7.25518 5.48059 9.07924 4.37067C10.9033 3.26076 12.985 2.64514 15.1194 2.58444C15.238 2.58444 15.3571 2.58444 15.4767 2.58444C18.6992 2.59399 21.7889 3.86908 24.0802 6.13498C26.3715 8.40087 27.681 11.4762 27.7265 14.6984C27.7719 17.9205 26.5498 21.0316 24.3234 23.3612C22.0969 25.6909 19.0444 27.0527 15.8235 27.1532L15.8099 27.1487Z" fill="currentColor"></path><path d="M10.2894 7.69007C10.1057 7.69366 9.92456 7.73407 9.75673 7.80892C9.5889 7.88377 9.43779 7.99154 9.31236 8.12584C8.95801 8.48923 7.96736 9.36377 7.91006 11.2003C7.85277 13.0369 9.13594 14.8538 9.31537 15.1086C9.49481 15.3635 11.7686 19.3306 15.5141 20.9395C17.7156 21.8879 18.6806 22.0507 19.3063 22.0507C19.5642 22.0507 19.7587 22.0236 19.9622 22.0115C20.6483 21.9693 22.1969 21.1762 22.5346 20.3137C22.8724 19.4512 22.895 18.6973 22.806 18.5465C22.7171 18.3957 22.4728 18.2872 22.1049 18.0942C21.737 17.9012 19.9321 16.9361 19.5928 16.8004C19.467 16.7419 19.3316 16.7066 19.1932 16.6964C19.1031 16.7011 19.0155 16.7278 18.938 16.774C18.8605 16.8203 18.7954 16.8847 18.7484 16.9618C18.4469 17.3372 17.7548 18.153 17.5225 18.3882C17.4718 18.4466 17.4093 18.4938 17.3392 18.5265C17.2691 18.5592 17.1928 18.5768 17.1154 18.5782C16.9728 18.5719 16.8333 18.5344 16.7068 18.4681C15.6135 18.0038 14.6167 17.339 13.768 16.5079C12.975 15.7263 12.3022 14.8315 11.7716 13.8526C11.5666 13.4726 11.7716 13.2766 11.9586 13.0987C12.1456 12.9208 12.3461 12.675 12.5391 12.4624C12.6975 12.2808 12.8295 12.0777 12.9312 11.8593C12.9838 11.7578 13.0104 11.6449 13.0085 11.5307C13.0067 11.4165 12.9765 11.3045 12.9206 11.2048C12.8317 11.0149 12.1667 9.14664 11.8546 8.39725C11.6013 7.75642 11.2997 7.73531 11.0358 7.7157C10.8187 7.70062 10.5699 7.69309 10.3211 7.68555H10.2894" fill="currentColor"></path></svg> kabari</a>
                    @endif
                    @if ($examregistration->exam_type_id==3)
                    <hr>
                    <a target="_blank" href="{{ route('report.thesis-exam-chief',$examregistration->id) }}" class="btn btn-sm btn-success">BA Hasil Ujian</a>
                    <a target="_blank" href="{{ route('report.thesis-exam-by-lecture',$examregistration->id) }}" class="btn btn-sm btn-success">Penilaian by Penguji</a>
                    <a target="_blank" href="{{ route('report.thesis-rev-by-lecture',$examregistration->id) }}" class="btn btn-sm btn-success">Revisi by Penguji</a>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
