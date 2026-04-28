<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DBS') }} - Dewan Bimbingan Skripsi</title>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800,900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])

        <style>
            :root {
                --dbs-blue: #1e40af;
                --dbs-blue-mid: #2563eb;
                --dbs-purple: #7c3aed;
                --dbs-cyan: #0891b2;
            }

            * { box-sizing: border-box; }

            body {
                background: #f1f5f9;
                font-family: 'Nunito', sans-serif;
                margin: 0;
            }

            /* ── HERO ── */
            .hero {
                background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #4f46e5 100%);
                padding: 72px 0 56px;
                color: #fff;
                position: relative;
                overflow: hidden;
            }
            .hero::before {
                content: '';
                position: absolute;
                top: -120px; right: -80px;
                width: 520px; height: 520px;
                background: radial-gradient(circle, rgba(99,102,241,.25) 0%, transparent 70%);
                pointer-events: none;
            }
            .hero::after {
                content: '';
                position: absolute;
                bottom: -140px; left: -60px;
                width: 420px; height: 420px;
                background: radial-gradient(circle, rgba(14,165,233,.18) 0%, transparent 70%);
                pointer-events: none;
            }
            .hero-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: rgba(255,255,255,.12);
                border: 1px solid rgba(255,255,255,.22);
                backdrop-filter: blur(8px);
                padding: 5px 14px 5px 10px;
                border-radius: 50px;
                font-size: 12.5px;
                font-weight: 700;
                letter-spacing: .3px;
                margin-bottom: 22px;
            }
            .hero-eyebrow-dot {
                width: 8px; height: 8px;
                background: #4ade80;
                border-radius: 50%;
                animation: blink 1.8s ease-in-out infinite;
            }
            @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.35} }

            .hero h1 {
                font-size: clamp(2rem, 4.5vw, 3.2rem);
                font-weight: 900;
                line-height: 1.15;
                margin: 0 0 18px;
                letter-spacing: -.5px;
            }
            .hero h1 span {
                background: linear-gradient(90deg, #93c5fd, #c4b5fd);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .hero-lead {
                font-size: 15.5px;
                color: rgba(255,255,255,.78);
                line-height: 1.65;
                max-width: 560px;
                margin-bottom: 36px;
            }
            .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
            .btn-hero-primary {
                display: inline-flex; align-items: center; gap: 8px;
                background: #fff; color: #1e3a8a;
                padding: 13px 26px; border-radius: 10px;
                font-weight: 800; font-size: 14.5px;
                text-decoration: none;
                transition: all .2s;
                box-shadow: 0 4px 14px rgba(0,0,0,.18);
            }
            .btn-hero-primary:hover {
                background: #eff6ff; color: #1e40af;
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0,0,0,.22);
            }
            .btn-hero-ghost {
                display: inline-flex; align-items: center; gap: 8px;
                background: rgba(255,255,255,.1); color: #fff;
                border: 1.5px solid rgba(255,255,255,.4);
                padding: 13px 26px; border-radius: 10px;
                font-weight: 700; font-size: 14.5px;
                text-decoration: none;
                transition: all .2s;
                backdrop-filter: blur(6px);
            }
            .btn-hero-ghost:hover {
                background: rgba(255,255,255,.2); color: #fff;
                border-color: rgba(255,255,255,.7);
                transform: translateY(-2px);
            }

            /* ── STATS BAR ── */
            .stats-bar {
                background: #fff;
                border-bottom: 1px solid #e2e8f0;
                padding: 0;
            }
            .stats-bar-inner {
                display: flex;
                align-items: stretch;
                justify-content: center;
                flex-wrap: wrap;
            }
            .stat-item {
                padding: 18px 36px;
                text-align: center;
                border-right: 1px solid #e2e8f0;
            }
            .stat-item:last-child { border-right: none; }
            .stat-num {
                font-size: 1.9rem;
                font-weight: 900;
                color: #1e40af;
                line-height: 1;
            }
            .stat-label {
                font-size: 12px;
                color: #64748b;
                margin-top: 3px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .4px;
            }

            /* ── MAIN CONTENT ── */
            .main-content { padding: 52px 0 20px; }

            /* ── SECTION HEADING ── */
            .section-heading { margin-bottom: 28px; }
            .section-heading h2 {
                font-size: 1.45rem;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 4px;
            }
            .section-heading p {
                font-size: 13.5px;
                color: #64748b;
                margin: 0;
            }

            /* ── FEATURE CARDS ── */
            .fcard {
                background: #fff;
                border-radius: 16px;
                padding: 28px 24px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 4px rgba(0,0,0,.06);
                height: 100%;
                display: flex;
                flex-direction: column;
                transition: all .25s;
            }
            .fcard:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 32px rgba(0,0,0,.10);
                border-color: #bfdbfe;
            }
            .fcard-icon {
                width: 50px; height: 50px;
                border-radius: 12px;
                display: flex; align-items: center; justify-content: center;
                font-size: 22px;
                margin-bottom: 18px;
                flex-shrink: 0;
            }
            .ic-blue   { background: #dbeafe; }
            .ic-green  { background: #dcfce7; }
            .ic-indigo { background: #e0e7ff; }
            .fcard-title {
                font-size: 16px;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 8px;
            }
            .fcard-text {
                font-size: 13.5px;
                color: #64748b;
                line-height: 1.65;
                margin: 0 0 22px;
                flex: 1;
            }
            .fcard-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 12px;
                border-radius: 8px;
                font-size: 12.5px;
                font-weight: 700;
            }
            .btn-primary-sm {
                display: inline-flex; align-items: center; gap: 6px;
                background: #2563eb; color: #fff;
                padding: 10px 20px; border-radius: 9px;
                font-weight: 700; font-size: 13.5px;
                text-decoration: none;
                transition: all .2s;
                align-self: flex-start;
            }
            .btn-primary-sm:hover { background: #1d4ed8; color: #fff; transform: translateY(-1px); }
            .btn-outline-sm {
                display: inline-flex; align-items: center; gap: 6px;
                background: transparent; color: #2563eb;
                border: 1.5px solid #2563eb;
                padding: 10px 20px; border-radius: 9px;
                font-weight: 700; font-size: 13.5px;
                text-decoration: none;
                transition: all .2s;
                align-self: flex-start;
            }
            .btn-outline-sm:hover { background: #eff6ff; color: #1d4ed8; transform: translateY(-1px); }

            /* ── SCHEDULE PANEL ── */
            .panel {
                background: #fff;
                border-radius: 16px;
                border: 1px solid #e2e8f0;
                overflow: hidden;
                box-shadow: 0 1px 4px rgba(0,0,0,.06);
                margin-bottom: 40px;
            }
            .panel-header {
                padding: 18px 24px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #e2e8f0;
            }
            .panel-header-grad {
                background: linear-gradient(90deg, #1e3a8a, #4f46e5);
                color: #fff;
                border-bottom: none;
            }
            .panel-title {
                font-size: 15px;
                font-weight: 800;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .badge-live {
                background: #ef4444;
                color: #fff;
                font-size: 10.5px;
                padding: 3px 9px;
                border-radius: 50px;
                font-weight: 800;
                letter-spacing: .5px;
                text-transform: uppercase;
                animation: blink 1.6s infinite;
            }
            .badge-count {
                background: rgba(255,255,255,.2);
                color: #fff;
                font-size: 12px;
                padding: 4px 12px;
                border-radius: 50px;
                font-weight: 700;
            }

            /* table inside panel */
            .panel .table { margin: 0; }
            .panel .table thead th {
                background: #f8fafc;
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .5px;
                color: #64748b;
                padding: 13px 20px;
                border-bottom: 2px solid #e2e8f0;
            }
            .panel .table tbody td {
                padding: 16px 20px;
                vertical-align: middle;
                font-size: 14px;
                border-bottom: 1px solid #f1f5f9;
            }
            .panel .table tbody tr:last-child td { border-bottom: none; }
            .panel .table tbody tr:hover { background: #f8fafc; }

            .time-block {
                background: #f1f5f9;
                border-radius: 10px;
                padding: 10px 14px;
                display: inline-block;
                min-width: 170px;
            }
            .time-block .tb-date { font-weight: 800; color: #0f172a; font-size: 13.5px; }
            .time-block .tb-time { font-weight: 800; color: #2563eb; font-size: 13px; margin-top: 3px; }
            .time-block .tb-room { color: #64748b; font-size: 12px; margin-top: 2px; }

            .row-num {
                width: 30px; height: 30px;
                background: #e0e7ff;
                color: #3730a3;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 13px;
                font-weight: 800;
            }

            .examiner-list { display: flex; flex-direction: column; gap: 4px; }

            /* ── FOOTER ── */
            .site-footer {
                background: #0f172a;
                color: #94a3b8;
                padding: 36px 0;
                margin-top: 60px;
            }
            .footer-brand {
                font-size: 22px;
                font-weight: 900;
                color: #fff;
                letter-spacing: -.5px;
            }
            .footer-sub { font-size: 13px; margin-top: 4px; }
            .footer-right { font-size: 13px; text-align: right; }

            @media (max-width: 767px) {
                .hero { padding: 52px 0 40px; }
                .stat-item { padding: 14px 20px; }
                .footer-right { text-align: left; margin-top: 12px; }
                .time-block { min-width: unset; }
            }
        </style>
    </head>
    <body>

        {{-- ═══════════ HERO ═══════════ --}}
        <section class="hero">
            <div class="container position-relative" style="z-index:1;">
                <div class="row">
                    <div class="col-lg-8 col-xl-7">
                        <div class="hero-eyebrow">
                            <span class="hero-eyebrow-dot"></span>
                            Program Studi S1 Pendidikan Matematika
                        </div>
                        <h1>Dewan Bimbingan<br><span>Skripsi</span></h1>
                        <p class="hero-lead">
                            Platform resmi pengelolaan ujian Seminar Proposal, Seminar Hasil Penelitian, dan Sidang Skripsi. Jadwal, penilaian, dan rekap kelulusan tersedia dalam satu sistem.
                        </p>
                        <div class="hero-actions">
                            @auth
                            <a href="{{ route('home') }}" class="btn-hero-primary">
                                ⚡ Masuk Dashboard
                            </a>
                            @else
                            <a href="{{ route('login') }}" class="btn-hero-primary">
                                🔐 Login Sistem
                            </a>
                            @endauth
                            <a href="{{ route('exam.result') }}" class="btn-hero-ghost">
                                📋 Cek Hasil Ujian
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════ STATS BAR ═══════════ --}}
        <div class="stats-bar">
            <div class="container p-0">
                <div class="stats-bar-inner">
                    <div class="stat-item">
                        <div class="stat-num">{{ $peserta->count() }}</div>
                        <div class="stat-label">Ujian Terjadwal</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">{{ $peserta->where('exam_type_id', 1)->count() }}</div>
                        <div class="stat-label">Sempro</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">{{ $peserta->where('exam_type_id', 2)->count() }}</div>
                        <div class="stat-label">Semhas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">{{ $peserta->where('exam_type_id', 3)->count() }}</div>
                        <div class="stat-label">Sidang</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════ MAIN ═══════════ --}}
        <div class="main-content">
            <div class="container">

                {{-- Feature Cards --}}
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="fcard">
                            <div class="fcard-icon ic-blue">🎓</div>
                            <div class="fcard-title">Sistem Informasi DBS</div>
                            <p class="fcard-text">Kelola pendaftaran ujian, penjadwalan, dan penilaian seminar proposal, seminar hasil penelitian, serta sidang skripsi secara terpadu.</p>
                            @auth
                            <a href="{{ route('home') }}" class="btn-primary-sm">Masuk Dashboard →</a>
                            @else
                            <a href="{{ route('login') }}" class="btn-primary-sm">Login Sekarang →</a>
                            @endauth
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fcard">
                            <div class="fcard-icon ic-green">📊</div>
                            <div class="fcard-title">Hasil Ujian Mahasiswa</div>
                            <p class="fcard-text">Mahasiswa dapat memeriksa hasil ujian seminar dan sidang skripsi beserta catatan revisi langsung melalui laman ini tanpa perlu login.</p>
                            <a href="{{ route('exam.result') }}" class="btn-outline-sm">Lihat Hasil Ujian →</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fcard">
                            <div class="fcard-icon ic-indigo">📅</div>
                            <div class="fcard-title">Jadwal Ujian</div>
                            <p class="fcard-text">Informasi jadwal ujian yang akan datang ditampilkan secara real-time, diurutkan berdasarkan tanggal, jam, dan ruang ujian.</p>
                            @if ($peserta->count() > 0)
                            <span class="fcard-badge bg-success text-white">
                                ✔ {{ $peserta->count() }} ujian terjadwal
                            </span>
                            @else
                            <span class="fcard-badge bg-secondary text-white">
                                Belum ada jadwal
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Jadwal Ujian --}}
                @if ($peserta->count() > 0)
                <div class="section-heading d-flex align-items-start justify-content-between">
                    <div>
                        <h2>Jadwal Ujian Mendatang</h2>
                        <p>Diurutkan berdasarkan tanggal &rsaquo; jam &rsaquo; ruang</p>
                    </div>
                    <span class="badge-live">● Live</span>
                </div>
                <div class="panel mb-5">
                    <div class="panel-header panel-header-grad">
                        <div class="panel-title">
                            📋 Ujian Terjadwal
                        </div>
                        <span class="badge-count">{{ $peserta->count() }} peserta</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Waktu &amp; Ruang</th>
                                    <th>Mahasiswa &amp; Judul</th>
                                    <th>Tim Penguji</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($peserta as $index => $ujian)
                                @php
                                    $bgExam = match((int) $ujian->exam_type_id) {
                                        1 => 'success',
                                        2 => 'primary',
                                        3 => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="row-num">{{ $index + 1 }}</span></td>
                                    <td>
                                        <div class="time-block">
                                            <div class="tb-date">{{ Carbon\Carbon::parse($ujian->exam_date)->isoFormat('ddd, D MMM Y') }}</div>
                                            <div class="tb-time">
                                                {{ Carbon\Carbon::createFromTimeString($ujian->exam_time)->isoFormat('HH:mm') }}
                                                &ndash;
                                                {{ Carbon\Carbon::createFromTimeString($ujian->exam_time)->addMinutes(60)->isoFormat('HH:mm') }}
                                            </div>
                                            <div class="tb-room">📍 Ruang {{ $ujian->room }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:800;color:#0f172a;font-size:15px;">{{ $ujian->student->name ?? '-' }}</div>
                                        <span class="badge bg-{{ $bgExam }} mt-1 mb-2" style="font-size:12px;padding:4px 10px;border-radius:6px;">{{ $ujian->examtype->name ?? '-' }}</span>
                                        <div style="font-size:13px;color:#475569;line-height:1.55;">{{ $ujian->title }}</div>
                                    </td>
                                    <td>
                                        <div class="examiner-list">
                                            <span class="badge bg-{{ $ujian->examiner1_id == $ujian->chief_id ? 'primary' : 'secondary' }}" style="font-size:12px;">P1: {{ $ujian->examiner1->name ?? '-' }}</span>
                                            <span class="badge bg-{{ $ujian->examiner2_id == $ujian->chief_id ? 'primary' : 'secondary' }}" style="font-size:12px;">P2: {{ $ujian->examiner2->name ?? '-' }}</span>
                                            <span class="badge bg-{{ $ujian->examiner3_id == $ujian->chief_id ? 'primary' : 'secondary' }}" style="font-size:12px;">P3: {{ $ujian->examiner3->name ?? '-' }}</span>
                                            <span class="badge bg-{{ $ujian->guide1_id == $ujian->chief_id ? 'primary' : 'secondary' }}" style="font-size:12px;">P4: {{ $ujian->guide1->name ?? '-' }}</span>
                                            <span class="badge bg-{{ $ujian->guide2_id == $ujian->chief_id ? 'primary' : 'secondary' }}" style="font-size:12px;">P5: {{ $ujian->guide2->name ?? '-' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Rekap Kelulusan --}}
                <div class="section-heading">
                    <h2>Rekap Kelulusan &amp; Ujian Skripsi</h2>
                    <p>Per tanggal {{ Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</p>
                </div>
                @include('informations.exam-recap')

            </div>
        </div>

        {{-- ═══════════ FOOTER ═══════════ --}}
        <footer class="site-footer">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="footer-brand">DBS</div>
                        <div class="footer-sub">Dewan Bimbingan Skripsi &mdash; Program Studi S1 Pendidikan Matematika</div>
                    </div>
                    <div class="col-md-5">
                        <div class="footer-right">
                            <div>{{ Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</div>
                            <div class="mt-1">
                                @auth
                                <a href="{{ route('home') }}" style="color:#93c5fd;text-decoration:none;font-weight:700;">Dashboard</a>
                                &nbsp;·&nbsp;
                                @endauth
                                <a href="{{ route('exam.result') }}" style="color:#93c5fd;text-decoration:none;font-weight:700;">Hasil Ujian</a>
                                &nbsp;·&nbsp;
                                @guest
                                <a href="{{ route('login') }}" style="color:#93c5fd;text-decoration:none;font-weight:700;">Login</a>
                                @endguest
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

    </body>
</html>
