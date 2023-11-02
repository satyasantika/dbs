@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if (session('warning'))
                        <div class="alert alert-warning">
                            {{ session('warning') }}
                        </div>
                    @endif

                    @includeWhen(auth()->user()->can('access dashboard dosen'),'dashboard.dosen')
                    @includeWhen(auth()->user()->can('access dashboard mahasiswa'),'dashboard.mahasiswa')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
