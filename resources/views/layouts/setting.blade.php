@extends('layouts.app')
@push('title')
    {{ isset($title) ? $title : '' }}
@endpush
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    @if (isset($route))
                    List {{ $context }} Angkatan {{ $generation }}
                    <a href="{{ route($route) }}" class="btn btn-primary btn-sm float-end">kembali</a>
                    @else
                    {{ ucFirst(request()->segment(1)) }} > {{ ucFirst(request()->segment(2)) }}
                    <a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                    @endif
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('warning'))
                        <div class="alert alert-warning">
                            {{ session('warning') }}
                        </div>
                    @endif
                    {{ $dataTable->table()}}
                    @stack('body')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@endpush
