@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ ucFirst(request()->segment(1)) }} {{ ucFirst(request()->segment(2)) }}</div>

                <div class="card-body">
                    <a class="btn btn-primary btn-sm" href="{{ route('users.create') }}">+ {{ request()->segment(2) }}</a>
                    <table class="table table-hover">
                        <tbody>
                            @forelse ($users as $user)
                            <tr>
                                <td>
                                    <a href="{{ route('userroles.edit',$user->id) }}" class="btn btn-outline-primary btn-sm">R</a>
                                    <a href="{{ route('userpermissions.edit',$user->id) }}" class="btn btn-outline-primary btn-sm">P</a>
                                    <a href="{{ route('users.edit',$user->id) }}" class="btn btn-outline-primary btn-sm">E</a>
                                    {{ $user->name }} <span class="text-primary">({{ $user->getRoleNames()->implode(', ') }})</span>
                                </td>
                                <td>
                                    <form id="activation-form" action="{{ route('users.activation',[ 'user'=>$user->id ]) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-{{ $user->na ? 'outline-danger' : 'primary' }} btn-sm float-end">
                                            {{ $user->na ? 'non aktif' : 'aktif' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                                Belum ada data
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


