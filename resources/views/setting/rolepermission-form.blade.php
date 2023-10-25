@extends('layouts.setting-form')

@push('header')
    Edit Permission untuk {{ $role->name }}
@endpush

@push('body')
<form id="formAction" action="{{ route('rolepermissions.update',$role->id) }}" method="post">
    @csrf
    @method('PUT')

    {{-- cek kecocokan setiap permission yang langsung dari Role --}}
    <div class="row">
        <div class="table-responsive">
            <table class="table table-hover">
                <tbody>
                    @foreach($permissions as $permission)
                    <tr>
                        <td>
                            @php
                                $role_permission = App\Models\Permission::where('name',$permission)->value('id');
                            @endphp
                                <input
                                    type="checkbox"
                                    name="permission[]"
                                    value="{{ $role_permission }}"
                                    id="{{ $role_permission }}"
                                    @checked(in_array($role_permission, $rolePermissions))
                                >
                                <label for="{{ $role_permission }}">{{ $permission }}</label>
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
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
