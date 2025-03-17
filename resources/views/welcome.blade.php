<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DBS') }} - @stack('title')</title>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div id="app">
            <div class="container">
                <div class="row justify-content-center mb-3 mt-4">
                    <div class="col-md-8 mx-auto text-center">
                        <h1 class="mb-3 fw-semibold lh-1">Dewan Bimbingan Skripsi</h1>
                        <p class="lead mb-4">Website resmi pengelolaan ujian seminar proposal, seminar hasil penelitian, dan sidang skripsi <br>di Program Studi S1 Pendidikan Matematika</p>
                    </div>
                </div>
                <div class="row justify-content-center mb-3">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                Informasi Sistem DBS
                            </div>
                            <div class="card-body">
                                @auth
                                Anda sudah login, silakan masuk halaman sistem<br>
                                <a href="{{ route('home') }}" class="btn btn-primary btn-sm">Home</a>
                                @else
                                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">LOGIN</a>
                                @endauth
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                Informasi Hasil Ujian
                            </div>
                            <div class="card-body">
                                <span class="text-primary">khusus mahasiswa:</span><br>
                                hasil ujian dapat dicek pada laman berikut:<br>
                                <a href="{{ route('exam.result') }}" class="btn btn-primary btn-sm">Cek Hasil Ujian</a>
                            </div>
                        </div>
                    </div>
                </div>
                @include('informations.exam-recap')
                @include('informations.exam-now')
            </div>
        </div>
    </body>
</html>
