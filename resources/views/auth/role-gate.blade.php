<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pilih Peran — {{ config('app.name', 'DBS') }}</title>
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

        /* decorative orbs — mirrors login/welcome hero */
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

        .gate-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            padding: 24px 16px;
        }

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
            padding: 28px;
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
        }

        .role-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .role-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.2);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 14.5px;
            transition: background .2s, border-color .2s, transform .2s;
        }
        .role-option:hover {
            background: rgba(255,255,255,.18);
            border-color: rgba(147,197,253,.7);
            transform: translateY(-1px);
        }
        .role-option .arrow {
            opacity: .7;
            transition: transform .2s;
        }
        .role-option:hover .arrow {
            transform: translateX(3px);
            opacity: 1;
        }

        .impersonate-note {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 18px;
            padding: 10px 14px;
            border-radius: 10px;
            background: rgba(251,191,36,.12);
            border: 1px solid rgba(251,191,36,.3);
            color: #fde68a;
            font-size: 12.5px;
            font-weight: 600;
        }

        .gate-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 22px;
        }

        .back-link, .logout-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            color: rgba(255,255,255,.55);
            text-decoration: none;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            font-family: inherit;
            transition: color .2s;
        }
        .back-link:hover, .logout-link:hover { color: rgba(255,255,255,.9); }
        .back-link svg { vertical-align: middle; margin-right: 4px; }

        /* logout confirmation modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
            padding: 16px;
        }
        .modal-overlay.is-open { display: flex; }
        .modal-card {
            background: rgba(30,41,59,.94);
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 16px;
            padding: 28px;
            max-width: 360px;
            width: 100%;
            box-shadow: 0 24px 60px rgba(0,0,0,.45);
            color: #fff;
        }
        .modal-card h2 { margin: 0 0 8px; font-size: 1.15rem; font-weight: 800; }
        .modal-card p { margin: 0 0 22px; font-size: 13.5px; color: rgba(255,255,255,.65); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .modal-btn-cancel {
            padding: 9px 16px;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,.2);
            background: transparent;
            color: #fff;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            font-family: inherit;
        }
        .modal-btn-cancel:hover { background: rgba(255,255,255,.08); }
        .modal-btn-confirm {
            padding: 9px 16px;
            border-radius: 9px;
            border: none;
            background: #ef4444;
            color: #fff;
            font-weight: 800;
            font-size: 13.5px;
            cursor: pointer;
            font-family: inherit;
        }
        .modal-btn-confirm:hover { background: #dc2626; }
    </style>
</head>
<body>
    <div class="gate-wrap">

        <a href="{{ route('welcome') }}" class="brand-badge">
            <span class="badge-dot"></span>
            Dewan Bimbingan Skripsi
        </a>

        <h1 class="brand-title">Pilih <span>Peran</span></h1>
        <p class="brand-sub">Akun Anda memiliki lebih dari satu peran. Silakan pilih portal yang ingin dibuka.</p>

        <div class="card-glass">
            @if (function_exists('is_impersonating') && is_impersonating())
                <div class="impersonate-note">
                    Anda sedang berpura-pura menjadi <strong>{{ auth()->user()->name }}</strong>.
                    <a href="{{ route('impersonate.leave') }}" style="color:#fde68a;text-decoration:underline;margin-left:auto;">Kembali ke Admin</a>
                </div>
            @endif

            <div class="role-list">
                @foreach ($options as $option)
                    <a href="{{ $option['url'] }}" class="role-option">
                        {{ $option['label'] }}
                        <span class="arrow">&rarr;</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="gate-footer">
            <a href="{{ route('welcome') }}" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Kembali ke halaman utama
            </a>

            <button type="button" class="logout-link" onclick="document.getElementById('logout-modal').classList.add('is-open')">Keluar</button>
        </div>

    </div>

    <div id="logout-modal" class="modal-overlay" onclick="if (event.target === this) this.classList.remove('is-open')">
        <div class="modal-card">
            <h2>Keluar dari aplikasi?</h2>
            <p>Anda perlu masuk kembali untuk mengakses portal ini.</p>
            <div class="modal-actions">
                <button type="button" class="modal-btn-cancel" onclick="document.getElementById('logout-modal').classList.remove('is-open')">Batal</button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="modal-btn-confirm">Ya, Keluar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.getElementById('logout-modal').classList.remove('is-open');
            }
        });
    </script>
</body>
</html>
