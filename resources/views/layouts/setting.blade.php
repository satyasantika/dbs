@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    {{ ucFirst(request()->segment(1)) }} > {{ ucFirst(request()->segment(2)) }}
                </div>
                <div class="card-body">
                    @stack('body')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
