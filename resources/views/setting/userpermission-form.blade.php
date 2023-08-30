@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Edit Permission untuk {{ $user->name }}
                </div>
                <form id="formAction" action="{{ route('userpermissions.update',$user->id) }}" method="post">
                    @csrf
                    @if ($user->id)
                        @method('PUT')
                    @endif
                    <div class="card-body">

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
                                                @checked(in_array($data, $userPermissions))
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
