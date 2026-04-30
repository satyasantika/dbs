<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ config('app.name', 'DBS') }}</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800,900&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        :root {
            --dbs-blue: #1e40af;
            --dbs-blue-mid: #2563eb;
            --dbs-purple: #7c3aed;
        }
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* decorative orbs — mirrors welcome hero */
        body::before {
            content: '';
            position: fixed;
            top: -140px; right: -100px;
            width: 560px; height: 560px;
            background: radial-gradient(circle, rgba(99,102,241,.28) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -160px; left: -80px;
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(14,165,233,.2) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ── card ── */
        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 24px 16px;
        }

        /* eyebrow badge */
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.22);
            backdrop-filter: blur(8px);
            padding: 5px 14px 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            letter-spacing: .3px;
            margin-bottom: 18px;
            text-decoration: none;
        }
        .brand-badge:hover { color: #e0e7ff; }
        .badge-dot {
            width: 8px; height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: blink 1.8s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.35} }

        .brand-title {
            font-size: 2rem;
            font-weight: 900;
            color: #fff;
            margin: 0 0 4px;
            letter-spacing: -.4px;
            line-height: 1.15;
        }
        .brand-title span {
            background: linear-gradient(90deg, #93c5fd, #c4b5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-sub {
            font-size: 13.5px;
            color: rgba(255,255,255,.6);
            margin: 0 0 32px;
        }

        .card-glass {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.16);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 36px 32px 32px;
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
        }

        .card-glass .form-label {
            color: rgba(255,255,255,.82);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .2px;
            margin-bottom: 6px;
        }

        .card-glass .form-control {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 9px;
            color: #fff;
            font-size: 14px;
            padding: 10px 14px;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .card-glass .form-control::placeholder { color: rgba(255,255,255,.35); }
        .card-glass .form-control:focus {
            background: rgba(255,255,255,.15);
            border-color: rgba(147,197,253,.7);
            box-shadow: 0 0 0 3px rgba(147,197,253,.18);
            color: #fff;
            outline: none;
        }
        .card-glass .form-control.is-invalid {
            border-color: #f87171;
        }
        .card-glass .invalid-feedback {
            color: #fca5a5;
            font-size: 12px;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: none;
            background: #fff;
            color: var(--dbs-blue);
            font-weight: 800;
            font-size: 14.5px;
            cursor: pointer;
            transition: all .2s;
            box-shadow: 0 4px 14px rgba(0,0,0,.2);
            margin-top: 8px;
        }
        .btn-login:hover {
            background: #eff6ff;
            color: var(--dbs-blue);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,.28);
        }
        .btn-login:active { transform: translateY(0); }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: rgba(255,255,255,.55);
            text-decoration: none;
            transition: color .2s;
        }
        .back-link:hover { color: rgba(255,255,255,.9); }
        .back-link svg { vertical-align: middle; margin-right: 4px; }
    </style>
</head>
<body>
    <div class="login-wrap">

        <a href="{{ route('welcome') }}" class="brand-badge">
            <span class="badge-dot"></span>
            Dewan Bimbingan Skripsi
        </a>

        <h1 class="brand-title">Selamat <span>Datang</span></h1>
        <p class="brand-sub">Masuk ke Sistem Informasi {{ config('app.name', 'DBS') }}</p>

        <div class="card-glass">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input
                        id="username"
                        type="text"
                        class="form-control @error('username') is-invalid @enderror"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        required
                        autocomplete="username"
                        autofocus>
                    @error('username')
                        <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        id="password"
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        name="password"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
                    @enderror
                </div>

                <button type="submit" class="btn-login">
                    Masuk &rarr;
                </button>
            </form>
        </div>

        <a href="{{ route('welcome') }}" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali ke halaman utama
        </a>

    </div>
</body>
</html>
