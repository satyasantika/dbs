@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ ucFirst(request()->segment(1)) }} {{ ucFirst(request()->segment(2)) }}</div>

                <div class="card-body">
                    <a class="btn btn-primary btn-sm" href="{{ route('roles.create') }}">+ {{ request()->segment(2) }}</a>
                    <table class="table table-hover">
                        <tbody>
                            @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <a href="{{ route('rolepermissions.edit',$role->id) }}" class="btn btn-outline-primary btn-sm">P</a>
                                    <a href="{{ route('roles.edit',$role->id) }}" class="btn btn-outline-primary btn-sm">E</a>
                                    {{ $role->name }}
                                </td>
                                <td>
                                    <form id="delete-form" action="{{ route('roles.destroy',$role->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $role->name }}?');">
                                            {{ __('delete') }}
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
