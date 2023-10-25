@extends('layouts.setting-form')

@push('header')
    Edit Permission untuk {{ $user->name }}
@endpush

@push('body')
<form id="formAction" action="{{ route('userpermissions.update',$user->id) }}" method="post">
    @csrf
    @if ($user->id)
        @method('PUT')
    @endif

    {{-- cek kecocokan setiap permission yang langsung dari user tanpa melalui Role --}}
    <div class="row">
        <div class="table-responsive">
            <table class="table table-hover">
                <tbody>
                    @foreach($permissions as $permission)
                    <tr>
                        <td>
                            @php
                                $user_permission = App\Models\Permission::where('name',$permission)->value('id');
                            @endphp
                                <input
                                    type="checkbox"
                                    name="permission[]"
                                    value="{{ $user_permission }}"
                                    id="{{ $user_permission }}"
                                    @checked(in_array($user_permission, $userPermissions))
                                >
                                <label for="{{ $user_permission }}">{{ $permission }}</label>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
