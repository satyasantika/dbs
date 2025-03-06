@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Dapatkan Revisi Hasil Ujian
                </div>

                <div class="card-body">
                    <form action="{{ route('set.exam.date') }}" method="post">
                        @csrf
                        {{-- NPM --}}
                        <div class="row mb-3">
                            <label for="student_id" class="col-md-4 col-form-label text-md-end">NPM</label>
                            <div class="col-md-8">
                                <input type="text" placeholder="ketik npm" value="" name="student_id" class="form-control" id="student_id" required>
                            </div>
                        </div>
                        {{-- Exam Date --}}
                        <div class="row mb-3">
                            <label for="exam_date" class="col-md-4 col-form-label text-md-end">Tanggal Ujian</label>
                            <div class="col-md-8">
                                <input type="date" placeholder="exam_date" value="" name="exam_date" class="form-control" id="exam_date">
                            </div>
                        </div>
                        {{-- submit Button --}}
                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-sm">Lihat ujian</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
