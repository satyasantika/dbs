@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Edit Roles untuk {{ $user->name }}
                </div>
                <form id="formAction" action="{{ route('userroles.update',$user->id) }}" method="post">
                    @csrf
                    @if ($user->id)
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <div class="row mb-2">
                            @foreach ($roles as $value)
                                <div class="col-md-3">
                                    <div class="input-group mb-2">
                                        <div class="input-group-text light">
                                            <input
                                                type="checkbox"
                                                name="roles[]"
                                                value="{{ $value->id }}"
                                                class="form-check-input mt-0"
                                                @checked(in_array($value->id, $userRoles))
                                            >
                                        </div>
                                        <input type="text" class="form-control" value="{{ $value->name }}" aria-label="Text input with checkbox">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
