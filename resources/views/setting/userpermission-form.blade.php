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
    @php
    // list permission
        $permission = [];
        foreach($permissions as $value){
            $url = explode(' ',$value)[1];
            foreach (['create ','read ','update ','delete '] as $action) {
                $permission_name = $action.$url;
                if (in_array($permission_name,$permissions->toArray())) {
                    if (!in_array($url,$permission)) {
                        array_push($permission,$url);
                    }
                }
            }
        }
    @endphp
    {{-- cek kecocokan setiap permission yang langsung dari user tanpa melalui Role --}}
    <div class="row">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col"></th>
                        <th scope="col"></th>
                        <th scope="col"></th>
                        <th scope="col"></th>
                        <th scope="col">Permission</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permission as $key => $url)
                    <tr>
                        @foreach (['create','read','update','delete'] as $action)
                        <td>
                            @php
                                $permission_name = $action.' '.$url;
                                $data = App\Models\Permission::where('name',$permission_name)->value('id');
                            @endphp
                            @if (in_array($permission_name,$permissions->toArray()))
                                <input
                                    type="checkbox"
                                    name="permission[]"
                                    value="{{ $data }}"
                                    id="{{ $data }}"
                                    @checked(in_array($data, $userPermissions))
                                >
                                <label for="{{ $data }}">{{ ucwords(substr($action,0,1)) }}</label>
                            @endif
                        </td>
                        @endforeach
                        <td>
                            {{ $url }}
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
