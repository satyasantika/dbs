@extends('layouts.setting')

@push('body')
<a class="btn btn-primary btn-sm" href="{{ route('permissions.create') }}">+ {{ request()->segment(2) }}</a>
<table class="table table-hover">
    <tbody>
        @forelse ($permissions as $permission)
        <tr>
            <td>
                <a href="{{ route('permissions.edit',$permission->id) }}" class="btn btn-outline-primary btn-sm">E</a>
                {{ $permission->name }}
            </td>
            <td>
                <form id="delete-form" action="{{ route('permissions.destroy',$permission->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm float-end" onclick="return confirm('Yakin akan menghapus {{ $permission->name }}?');">
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
