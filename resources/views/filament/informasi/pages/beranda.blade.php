<x-filament-panels::page>
    <style>
        :root {
            --dbs-blue: #1e40af;
            --dbs-blue-mid: #2563eb;
        }

        {{-- Halaman publik ini sengaja tanpa app-shell Filament biasa (topbar
             disembunyikan, tidak ada heading otomatis — lihat Beranda::
             getHeading()) dan footer mengikuti alur halaman, bukan fixed
             seperti panel lain (custom-styles.blade.php). Warna/gaya footer
             tetap sama (gradient gelap) supaya masih senada dengan tema. --}}
        .fi-topbar { display: none !important; }

        .fi-main {
            height: auto !important;
            min-height: 100vh;
            overflow-y: visible !important;
            padding-bottom: 0 !important;
        }

        .fi-page-footer {
            position: static !important;
            inset: auto !important;
            height: auto !important;
            overflow: visible !important;
            margin-top: 2.5rem;
        }

        .fi-page-footer-inner {
            flex: none !important;
            padding: 0.875rem 31px !important;
        }

        .beranda-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #4f46e5 100%);
            border-radius: 20px;
            padding: 48px 36px;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .beranda-hero::before {
            content: '';
            position: absolute;
            top: -120px; right: -80px;
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(99,102,241,.25) 0%, transparent 70%);
            pointer-events: none;
        }
        .beranda-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.22);
            padding: 5px 14px 5px 10px;
            border-radius: 50px;
            font-size: 12.5px;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .beranda-hero-dot {
            width: 8px; height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: beranda-blink 1.8s ease-in-out infinite;
        }
        @keyframes beranda-blink { 0%,100%{opacity:1} 50%{opacity:.35} }
        .beranda-hero h1 {
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            font-weight: 900;
            line-height: 1.15;
            margin: 0 0 14px;
            position: relative;
        }
        .beranda-hero-lead {
            font-size: 14.5px;
            color: rgba(255,255,255,.8);
            line-height: 1.6;
            max-width: 560px;
            margin-bottom: 24px;
            position: relative;
        }
        .beranda-hero-actions { display: flex; flex-wrap: wrap; gap: 10px; position: relative; }
        .beranda-btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            background: #fff; color: #1e3a8a;
            padding: 11px 22px; border-radius: 10px;
            font-weight: 800; font-size: 13.5px;
            text-decoration: none;
        }
        .beranda-btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,.1); color: #fff;
            border: 1.5px solid rgba(255,255,255,.4);
            padding: 11px 22px; border-radius: 10px;
            font-weight: 700; font-size: 13.5px;
            text-decoration: none;
        }

        .beranda-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1px;
            background: #e2e8f0;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 32px;
        }
        .beranda-stat-item {
            background: #fff;
            padding: 16px 20px;
            text-align: center;
        }
        .beranda-stat-num { font-size: 1.7rem; font-weight: 900; color: var(--dbs-blue); line-height: 1; }
        .beranda-stat-label { font-size: 11.5px; color: #64748b; margin-top: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }

        .beranda-section-heading { margin-bottom: 18px; }
        .beranda-section-heading h2 { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
        .beranda-section-heading p { font-size: 13px; color: #64748b; margin: 0; }

        {{-- Kartu Jadwal Ujian Mendatang: padding vertikal tipis pada
             kontainer luar. PENTING: bukan .fi-ta-col-wrp — itu class yang
             sama dipakai Filament untuk MEMBUNGKUS TIAP kolom Stack secara
             individual (header/judul/waktu-penguji masing-masing dapat
             .fi-ta-col-wrp sendiri), jadi memberi padding di situ menumpuk
             padding tiap kolom + gap stack di antaranya (dulu ketauan jadi
             ~29px). Padding kartu yang sebenarnya cuma sekali per-record,
             di div tak berclass "flex w-full flex-col gap-y-3 py-4" (lihat
             vendor/filament/tables/resources/views/index.blade.php) —
             ditarget lewat class Tailwind gap-y-3 yang menempel di situ. --}}
        .jadwal-cards .fi-ta-record .gap-y-3 { padding-top: .625rem; padding-bottom: .625rem; }
        {{-- Sama dengan gap .jadwal-body-row (Waktu Lokasi <-> Tim Penguji)
             — ini satu-satunya sumber jarak antar Nama/Judul/baris bawah. --}}
        .jadwal-card-stack { gap: .6rem; }

        {{-- Box abu-abu dipakai bersama oleh Judul/Waktu/Penguji, & label
             kecil di atasnya juga satu sumber (dulu ada 1 versi per blok). --}}
        .jadwal-box { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: .5rem; padding: .45rem .6rem; }
        .jadwal-group-label { display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #94a3b8; margin-bottom: .25rem; }

        {{-- Baris 1: badge Jenis Ujian + NIM sejajar, lalu nama di baris
             baru (biar badge tak rusak walau nama sangat panjang). --}}
        .jadwal-badge-row { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; }
        {{-- max-width+ellipsis jaga-jaga nama dosen yang sangat panjang
             supaya terpotong rapi, bukan overlap/merusak card. --}}
        .jadwal-badge { display: inline-flex; align-items: center; max-width: 100%; padding: .15rem .5rem; border-radius: .375rem; font-size: 10.5px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
        {{-- Warna per jenis ujian selaras App\Enums\ExamTypeCode (dipakai juga
             di badge Filament lain): sempro=warning, semhas=info, sidang=success. --}}
        .jadwal-badge-jenis-sempro { background: #fef3c7; color: #92400e; }
        .jadwal-badge-jenis-semhas { background: #dbeafe; color: #1d4ed8; }
        .jadwal-badge-jenis-sidang { background: #dcfce7; color: #15803d; }
        .jadwal-badge-jenis-default { background: #eef2ff; color: #4338ca; }
        .jadwal-badge-nim { background: #f1f5f9; color: #475569; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .jadwal-nama { margin-top: .25rem; font-size: 1rem; font-weight: 800; color: #0f172a; line-height: 1.2; }

        {{-- Baris 2: Judul Tugas Akhir — dibungkus .jadwal-box, judul italic. --}}
        .jadwal-judul-text { margin: 0; font-size: 12px; font-style: italic; color: #475569; line-height: 1.4; }
        .jadwal-judul-empty { color: #cbd5e1; font-style: normal; }

        {{-- Baris 3: layout asimetris — kolom Waktu sempit (tak melar) +
             kolom Penguji melar, sejajar horizontal mulai breakpoint sm
             (640px), tumpuk vertikal di layar sempit. Keduanya dibungkus
             .jadwal-box. --}}
        .jadwal-body-row { display: flex; flex-direction: column; gap: .6rem; font-size: 12px; }
        .jadwal-waktu-col { flex-shrink: 0; display: flex; flex-direction: column; color: #475569; }
        .jadwal-waktu-list { display: flex; flex-direction: column; gap: .25rem; }
        .jadwal-waktu-item { display: inline-flex; align-items: center; gap: .3rem; width: fit-content; white-space: nowrap; background: #fff; padding: .2rem .45rem; border-radius: .3rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); font-weight: 600; }
        .jadwal-waktu-icon { font-size: 11px; line-height: 1; flex-shrink: 0; }
        .jadwal-penguji-col { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; }
        .jadwal-penguji-rows { display: flex; flex-direction: column; gap: .3rem; }

        {{-- Hierarki penguji: Ketua sendirian di baris pertama (biru +
             mahkota), Anggota mengalir bebas, Pembimbing selalu baris
             sendiri. --}}
        .jadwal-ketua-row { width: 100%; display: flex; }
        .jadwal-anggota-row, .jadwal-pembimbing-row { width: 100%; display: flex; flex-wrap: wrap; gap: .25rem; }
        .jadwal-badge-penguji { background: #f1f5f9; color: #475569; }
        .jadwal-badge-pembimbing { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
        .jadwal-badge-ketua { background: #dbeafe; color: #1d4ed8; font-weight: 800; border: 1px solid #bfdbfe; }

        @media (min-width: 640px) {
            .jadwal-body-row { flex-direction: row; gap: .9rem; }
            .jadwal-waktu-col { width: auto; min-width: 130px; }
        }

        {{-- Rekap: satu Card besar membungkus bento grid ringkasan +
             tabel per-angkatan. --}}
        .beranda-rekap-card-outer {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            padding: 24px;
        }
        .beranda-rekap-all-heading { font-size: .8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; }
        .beranda-rekap-per-angkatan-heading { font-size: .8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin: 24px 0 10px; }

        {{-- Bento grid: kartu Total (besar, ring persentase Lulus) + Lulus +
             Belum Lulus, lalu satu baris 3 kartu kecil bottleneck (Sempro/
             Semhas/Sidang) di bawahnya. --}}
        .beranda-bento-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .beranda-bento-card {
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .beranda-bento-card-total { grid-column: span 2; background: #eff6ff; border: 1px solid #dbeafe; }
        .beranda-bento-card-lulus { grid-column: span 1; background: #f0fdf4; border: 1px solid #dcfce7; flex-direction: column; align-items: flex-start; justify-content: center; }
        .beranda-bento-card-belum-lulus { grid-column: span 1; background: #fffbeb; border: 1px solid #fef3c7; flex-direction: column; align-items: flex-start; justify-content: center; }
        .beranda-bento-num { font-size: 2rem; font-weight: 900; color: #0f172a; line-height: 1; }
        .beranda-bento-label { font-size: 11.5px; color: #64748b; margin-top: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .beranda-bento-sub { font-size: .85rem; font-weight: 700; color: #64748b; margin-top: 4px; }

        {{-- Ring: dua lingkaran bertumpuk, tanpa SVG — lingkaran luar diwarnai
             conic-gradient() (dari Beranda::ringStyle()), lingkaran dalam
             (warna putih, sama dengan card) "melubangi" tengahnya. --}}
        .beranda-bento-ring { position: relative; width: 92px; height: 92px; border-radius: 50%; flex-shrink: 0; }
        .beranda-bento-ring-inner {
            position: absolute; top: 12px; left: 12px; width: 68px; height: 68px;
            background: #eff6ff; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .beranda-bento-ring-pct { font-size: .95rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .beranda-bento-ring-label { font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; margin-top: 2px; }

        .beranda-bento-bottleneck-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .beranda-bento-mini-card { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 10px 14px; text-align: center; }
        .beranda-bento-mini-num { font-size: 1.15rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .beranda-bento-mini-label { font-size: 10.5px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-top: 3px; }

        @media (max-width: 900px) {
            .beranda-bento-grid { grid-template-columns: repeat(2, 1fr); }
            .beranda-bento-card-total { grid-column: span 2; }
            .beranda-bento-card-lulus, .beranda-bento-card-belum-lulus { grid-column: span 1; }
        }
        @media (max-width: 480px) {
            .beranda-bento-grid { grid-template-columns: 1fr; }
            .beranda-bento-card-total, .beranda-bento-card-lulus, .beranda-bento-card-belum-lulus { grid-column: span 1; }
            .beranda-bento-bottleneck-row { grid-template-columns: 1fr; }
        }

        .beranda-rekap-metric-reg { font-size: 10.5px; color: #16a34a; font-weight: 700; margin-top: 2px; display: block; }
    </style>

    {{-- ═══════ HERO ═══════ --}}
    <section class="beranda-hero">
        <div class="beranda-hero-eyebrow">
            <span class="beranda-hero-dot"></span>
            Program Studi S1 Pendidikan Matematika
        </div>
        <h1>Dewan Bimbingan Skripsi</h1>
        <p class="beranda-hero-lead">
            Platform resmi pengelolaan ujian Seminar Proposal, Seminar Hasil Penelitian, dan Sidang Skripsi.
            Jadwal, penilaian, dan rekap kelulusan tersedia dalam satu sistem.
        </p>
        <div class="beranda-hero-actions">
            @auth
                <a href="{{ route('home') }}" class="beranda-btn-primary">⚡ Masuk Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="beranda-btn-primary">🔐 Login Sistem</a>
            @endauth
            <a href="{{ route('exam.result') }}" class="beranda-btn-ghost">📋 Cek Hasil Ujian</a>
        </div>
    </section>

    {{-- ═══════ STATS ═══════ --}}
    @php($stats = $this->jadwalStats())
    <div class="beranda-stats">
        <div class="beranda-stat-item">
            <div class="beranda-stat-num">{{ $stats['total'] }}</div>
            <div class="beranda-stat-label">Ujian Terjadwal</div>
        </div>
        <div class="beranda-stat-item">
            <div class="beranda-stat-num">{{ $stats['sempro'] }}</div>
            <div class="beranda-stat-label">Sempro</div>
        </div>
        <div class="beranda-stat-item">
            <div class="beranda-stat-num">{{ $stats['semhas'] }}</div>
            <div class="beranda-stat-label">Semhas</div>
        </div>
        <div class="beranda-stat-item">
            <div class="beranda-stat-num">{{ $stats['sidang'] }}</div>
            <div class="beranda-stat-label">Sidang</div>
        </div>
    </div>

    {{-- ═══════ JADWAL UJIAN (card grid, semua ditampilkan) ═══════ --}}
    <div class="beranda-section-heading">
        <h2>Jadwal Ujian Mendatang</h2>
        <p>Setiap kartu menampilkan jenis ujian, mahasiswa, judul, waktu, dan para penguji — diurutkan berdasarkan tanggal &rsaquo; jam &rsaquo; ruang, seluruh jadwal ditampilkan tanpa dibatasi tinggi layar.</p>
    </div>
    <div data-grid-fit="none" class="mb-8 jadwal-cards">
        {{ $this->table }}
    </div>

    {{-- ═══════ REKAP KELULUSAN — satu Card, statistik semua angkatan + card per angkatan ═══════ --}}
    <div class="beranda-section-heading">
        <h2>Rekap Kelulusan &amp; Ujian Skripsi</h2>
        <p>Per tanggal {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }} — pilih angkatan untuk rincian tiap kategori.</p>
    </div>
    <div class="beranda-rekap-card-outer">
        @php($all = $this->rekapSemuaAngkatan())
        <div class="beranda-rekap-all-heading">Semua Angkatan</div>

        {{-- ═══ Bento grid: Total (+ ring % Lulus), Lulus, Belum Lulus,
             lalu satu baris 3 kartu kecil bottleneck (Sempro/Semhas/Sidang). ═══ --}}
        <div class="beranda-bento-grid">
            <div class="beranda-bento-card beranda-bento-card-total">
                <div>
                    <div class="beranda-bento-num">{{ $all['total'] }}</div>
                    <div class="beranda-bento-label">Total Mahasiswa</div>
                </div>
                <div class="beranda-bento-ring" style="{{ $this->ringStyle($all['lulus_pct']) }}">
                    <div class="beranda-bento-ring-inner">
                        <span class="beranda-bento-ring-pct">{{ $all['lulus_pct'] }}%</span>
                        <span class="beranda-bento-ring-label">Lulus</span>
                    </div>
                </div>
            </div>
            <div class="beranda-bento-card beranda-bento-card-lulus">
                <div class="beranda-bento-num">{{ $all['lulus'] }}</div>
                <div class="beranda-bento-label">Sudah Lulus</div>
                <div class="beranda-bento-sub">{{ $all['lulus_pct'] }}%</div>
            </div>
            <div class="beranda-bento-card beranda-bento-card-belum-lulus">
                <div class="beranda-bento-num">{{ $all['belum_lulus'] }}</div>
                <div class="beranda-bento-label">Belum Lulus</div>
                <div class="beranda-bento-sub">{{ $all['belum_lulus_pct'] }}%</div>
            </div>
        </div>
        <div class="beranda-bento-bottleneck-row">
            <div class="beranda-bento-mini-card">
                <div class="beranda-bento-mini-num">{{ $all['belum_sempro'] }}</div>
                <div class="beranda-bento-mini-label">Belum Ujian Sempro</div>
            </div>
            <div class="beranda-bento-mini-card">
                <div class="beranda-bento-mini-num">{{ $all['akan_semhas'] }}</div>
                <div class="beranda-bento-mini-label">Belum Ujian Semhas</div>
            </div>
            <div class="beranda-bento-mini-card">
                <div class="beranda-bento-mini-num">{{ $all['akan_sidang'] }}</div>
                <div class="beranda-bento-mini-label">Belum Ujian Sidang</div>
            </div>
        </div>

        <div class="beranda-rekap-per-angkatan-heading">Per Angkatan</div>
        {{-- Table native (bukan Filament Table Builder) karena datanya array
             hasil agregasi ($this->rekap()), bukan Eloquent — gaya mengikuti
             resources/views/filament/dosen/pages/partials/graduation-semester-recap.blade.php
             (border tipis, divide-y, kolom angka rata kanan). --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[52rem] text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th scope="col" class="px-3 py-2 text-left font-medium text-gray-500">Tahun Angkatan</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Total</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Sudah Lulus</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Belum Lulus</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Belum Ujian Sempro</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Belum Ujian Semhas</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Belum Ujian Sidang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($this->rekap() as $row)
                        <tr>
                            <th scope="row" class="px-3 py-2 text-left font-semibold text-gray-950">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Total Mahasiswa']) }}" class="hover:underline">
                                    Angkatan {{ $row['angkatan'] }}
                                </a>
                            </th>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Total Mahasiswa']) }}" class="hover:underline">
                                    {{ $row['total'] }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Lulus']) }}" class="block hover:underline">
                                    <div>{{ $row['lulus'] }} <span class="text-gray-500 font-normal">({{ $row['lulus_pct'] }}%)</span></div>
                                    <div class="mt-1 h-1.5 w-full min-w-[64px] rounded-full bg-gray-200">
                                        <div class="h-1.5 rounded-full bg-green-500" style="width: {{ $row['lulus_pct'] }}%"></div>
                                    </div>
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Belum Lulus']) }}" class="hover:underline">
                                    {{ $row['belum_lulus'] }} <span class="text-gray-500 font-normal">({{ $row['belum_lulus_pct'] }}%)</span>
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Belum Sempro']) }}" class="block hover:underline">
                                    <div>{{ $row['belum_sempro'] }}</div>
                                    @if ($row['belum_sempro_reg'] > 0)
                                        <span class="beranda-rekap-metric-reg">{{ $row['belum_sempro_reg'] }} reg</span>
                                    @endif
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Akan Semhas']) }}" class="block hover:underline">
                                    <div>{{ $row['akan_semhas'] }}</div>
                                    @if ($row['akan_semhas_reg'] > 0)
                                        <span class="beranda-rekap-metric-reg">{{ $row['akan_semhas_reg'] }} reg</span>
                                    @endif
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Akan Sidang']) }}" class="block hover:underline">
                                    <div>{{ $row['akan_sidang'] }}</div>
                                    @if ($row['akan_sidang_reg'] > 0)
                                        <span class="beranda-rekap-metric-reg">{{ $row['akan_sidang_reg'] }} reg</span>
                                    @endif
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-3 text-gray-500">Belum ada data angkatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
