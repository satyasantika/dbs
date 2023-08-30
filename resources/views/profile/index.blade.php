@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login ').config('app.name', 'Laravel') }}</div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table small-font table-sm" id="profile-table" role="grid">
                            <tbody>
                                <tr>
                                    <td colspan="2">
                                        <a class="btn btn-primary btn-sm action" href="{{ route('profiles.edit',$user->id) }}">Edit</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Nama Lengkap</td>
                                    <td>{{ $user->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        @role('admin')Username @endrole
                                        @role('mahasiswa')NPM @endrole
                                        @role('dosen')NIDN @endrole
                                    </td>
                                    <td>{{ $user->username ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td>{{ $user->email ?? '-' }}</td>
                                </tr>
                                @role('mahasiswa')
                                <tr>
                                    <td>Tempat Lahir</td>
                                    <td>{{ $user->birth_place ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Tanggal Lahir</td>
                                    <td>{{ $user->birth_date ? $user->birth_date->format('d-m-Y') : '-' }}</td>
                                </tr>
                                @endrole
                                <tr>
                                    <td>Alamat</td>
                                    <td>{{ $user->address ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Nomor WA</td>
                                    <td>
                                        {{ $user->phone ?? '-' }}
                                        <br><span class="text-danger">pastikan sesuai format, contoh: 8512XXXXX</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
