@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ $permission->id ? 'Edit' : 'Tambah' }} {{ ucFirst(request()->segment(2)) }}
                </div>
                <form id="formAction" action="{{ $permission->id ? route('permissions.update',$permission->id) : route('permissions.store') }}" method="post">
                    @csrf
                    @if ($permission->id)
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="permissionName" class="col-md-4 col-form-label text-md-end">Name</label>
                            <div class="col-md-6">
                                <input type="text" placeholder="Permission name" value="{{ $permission->name }}" name="name" class="form-control" id="permissionName" required autofocus>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="guardName" class="col-md-4 col-form-label text-md-end">Guard</label>
                            <div class="col-md-6">
                                <input type="text" placeholder="Guard name" value="{{ $permission->guard_name }}" name="guard_name" class="form-control" id="guardName" required>
                            </div>
                        </div>
                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
