@php
    $homeUrl = route('welcome');
    $homeLabel = 'Halaman Utama';

    if (auth()->check()) {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $homeUrl = url('/admin');
            $homeLabel = 'Dashboard Admin';
        } elseif ($user->hasRole('dosen')) {
            $homeUrl = url('/home');
            $homeLabel = 'Dashboard Dosen';
        } else {
            $homeUrl = route('dashboard');
            $homeLabel = 'Dashboard';
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name', 'DBS') }}</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        :root {
            --dbs-blue: #1e40af;
            --dbs-blue-mid: #2563eb;
            --dbs-purple: #7c3aed;
            --accent: @yield('accent', '#f87171');
            --accent-soft: @yield('accent-soft', 'rgba(248,113,113,.18)');
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
            overflow-x: hidden;
            padding: 24px 16px;
        }

        body::before {
            content: '';
            position: fixed;
            top: -140px;
            right: -100px;
            width: 560px;
            height: 560px;
            background: radial-gradient(circle, rgba(99,102,241,.28) 0%, transparent 70%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -160px;
            left: -80px;
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(14,165,233,.2) 0%, transparent 70%);
            pointer-events: none;
        }

        .error-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 560px;
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
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: blink 1.8s ease-in-out infinite;
        }

        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.35} }

        .error-card {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.16);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 36px 32px 32px;
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
        }

        .error-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 72px;
            padding: 8px 16px;
            border-radius: 12px;
            background: var(--accent-soft);
            border: 1px solid rgba(255,255,255,.12);
            color: #fff;
            font-size: 1.75rem;
            font-weight: 900;
            letter-spacing: -.5px;
            margin-bottom: 18px;
        }

        .error-heading {
            margin: 0 0 12px;
            font-size: 1.65rem;
            font-weight: 900;
            color: #fff;
            line-height: 1.25;
            letter-spacing: -.3px;
        }

        .error-description {
            margin: 0 0 18px;
            font-size: 14.5px;
            line-height: 1.65;
            color: rgba(255,255,255,.72);
        }

        .error-detail {
            margin: 0 0 22px;
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(15,23,42,.35);
            border: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.88);
            font-size: 13.5px;
            line-height: 1.55;
            word-break: break-word;
        }

        .error-detail-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: rgba(255,255,255,.45);
            margin-bottom: 6px;
        }

        .error-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }

        .error-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.62);
            font-size: 12px;
            font-weight: 700;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-error {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s;
            border: none;
            font-family: inherit;
        }

        .btn-back {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.22);
            color: rgba(255,255,255,.88);
        }

        .btn-back:hover {
            background: rgba(255,255,255,.16);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-home {
            background: #fff;
            color: var(--dbs-blue);
            box-shadow: 0 4px 14px rgba(0,0,0,.2);
        }

        .btn-home:hover {
            background: #eff6ff;
            color: var(--dbs-blue);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,.28);
        }

        .btn-secondary {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.18);
            color: rgba(255,255,255,.85);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,.14);
            color: #fff;
            transform: translateY(-1px);
        }

        @media (max-width: 480px) {
            .error-card { padding: 28px 22px 24px; }
            .error-heading { font-size: 1.4rem; }
            .btn-error { flex: 1 1 100%; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="error-wrap">
        <a href="{{ route('welcome') }}" class="brand-badge">
            <span class="badge-dot"></span>
            Dewan Bimbingan Skripsi
        </a>

        <div class="error-card">
            <div class="error-code">@yield('code')</div>
            <h1 class="error-heading">@yield('heading')</h1>
            <p class="error-description">@yield('description')</p>

            @hasSection('detail')
                <div class="error-detail">
                    <span class="error-detail-label">@yield('detail-label', 'Detail')</span>
                    @yield('detail')
                </div>
            @endif

            @hasSection('meta')
                <div class="error-meta">
                    @yield('meta')
                </div>
            @endif

            <div class="error-actions">
                <button type="button" class="btn-error btn-back" onclick="goBack(@json($homeUrl))">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                    Kembali
                </button>
                <a href="{{ $homeUrl }}" class="btn-error btn-home">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5z"/></svg>
                    {{ $homeLabel }}
                </a>
                @yield('extra-actions')
            </div>
        </div>
    </div>

    <script>
        function goBack(fallbackUrl) {
            if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) {
                history.back();
                return;
            }

            window.location.href = fallbackUrl;
        }
    </script>
    @stack('scripts')
</body>
</html>
