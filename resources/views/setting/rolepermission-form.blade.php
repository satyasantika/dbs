@extends('layouts.setting-form')

@push('header')
    Edit Permission untuk {{ $role->name }}
@endpush

@push('body')
<form id="formAction" action="{{ route('rolepermissions.update',$role->id) }}" method="post">
    @csrf
    @method('PUT')
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
    {{-- cek kecocokan setiap permission yang langsung dari Role --}}
    @foreach($permission as $key => $url)
    <div class="row">
        <div class="col col-md-4">
            @foreach (['create','read','update','delete'] as $action)
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
                        @checked(in_array($data, $rolePermissions))
                    >
                    <label for="{{ $data }}">{{ ucwords(substr($action,0,1)) }}</label>
                @endif
            @endforeach
        </div>
        <div class="col col-md-8" id="heading{{ $key }}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse{{ $key }}" aria-expanded="false"
                aria-controls="collapse{{ $key }}">
                {{ $url }}
            </button>
        </div>
    </div>
    @endforeach
    <div class="row mb-0">
        <div class="col-md-8 offset-md-4">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">Close</a>
        </div>
    </div>
</form>
@endpush
