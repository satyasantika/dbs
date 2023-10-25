@extends('layouts.setting')

{{-- @push('body')
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
                <form id="activation-form" action="{{ route('users.activation',$user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-{{ $user->is_active ? 'primary' : 'outline-danger' }} btn-sm float-end">
                        {{ $user->is_active ? 'aktif' : 'non aktif' }}
                    </button>
                </form>
            </td>
        </tr>
        @empty
            Belum ada data
        @endforelse
    </tbody>
</table>
@endpush --}}

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">Manage Users</div>
            <div class="card-body">
                {{ $dataTable->table() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@endpush
