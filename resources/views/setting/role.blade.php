@extends('layouts.setting')

@push('body')
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
@endpush
