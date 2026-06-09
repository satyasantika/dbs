@extends('layouts.general')
@push('title')
    Menilai {{ $scoring->registration->student->name }}
@endpush
@push('header')
<br>Penilaian {{ $scoring->registration->examtype->name }}
<a href="{{ $returnUrl }}" class="btn btn-outline-primary btn-sm float-end">kembali</a>
@endpush

@push('body')
    @include('examination.partials.scoring-form-fields', array_merge($formData, [
        'returnUrl' => $returnUrl,
        'formAction' => route('scoring.update', $scoring->id),
    ]))
@endpush
