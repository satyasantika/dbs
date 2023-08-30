@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ $role->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
                </div>
                <form id="formAction" action="{{ $role->id ? route('roles.update',$role->id) : route('roles.store') }}" method="post">
                    @csrf
                    @if ($role->id)
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="roleName" class="col-md-4 col-form-label text-md-end">Name</label>
                            <div class="col-md-6">
                                <input type="text" placeholder="Role name" value="{{ $role->name }}" name="name" class="form-control" id="roleName" required autofocus>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="guardName" class="col-md-4 col-form-label text-md-end">Guard</label>
                            <div class="col-md-6">
                                <input type="text" placeholder="Guard name" value="{{ $role->guard_name }}" name="guard_name" class="form-control" id="guardName" required>
                            </div>
                        </div>
                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
