@extends('layouts.setting')

@push('body')
<a class="btn btn-primary btn-sm" href="{{ route('navigations.create') }}">+ {{ request()->segment(2) }}</a>
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th scope="col"></th>
                <th scope="col"></th>
                <th scope="col">Menu</th>
                <th scope="col">URL</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($navigations as $navigation)
            <tr>
                <td>
                    <a href="{{ route('navigations.edit',$navigation->id) }}" class="btn btn-outline-primary btn-sm">E</a>
                </td>
                <td>
                    {{ $navigation->order }}
                </td>
                <td>
                    {{ $navigation->name }}
                </td>
                <td>
                    {{ $navigation->url }}
                </td>
                <td>
                    <form id="delete-form" action="{{ route('navigations.destroy',$navigation->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin akan menghapus {{ $navigation->name }}?');">
                            {{ __('D') }}
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
@endpush
