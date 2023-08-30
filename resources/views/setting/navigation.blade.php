@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ ucFirst(request()->segment(1)) }} {{ ucFirst(request()->segment(2)) }}</div>

                <div class="card-body">
                    <a class="btn btn-primary btn-sm" href="{{ route('navigations.create') }}">+ {{ request()->segment(2) }}</a>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col"></th>
                                <th scope="col">Menu</th>
                                <th scope="col">URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($navigations as $navigation)
                            <tr>
                                <td>
                                    <a href="{{ route('navigations.edit',$navigation->id) }}" class="btn btn-outline-primary btn-sm">E</a>
                                    <form id="delete-form" action="{{ route('navigations.destroy',$navigation->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin akan menghapus {{ $navigation->name }}?');">
                                            {{ __('D') }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    {{ $navigation->order }} - {{ $navigation->name }}
                                </td>
                                <td>
                                    {{ $navigation->url }}
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
