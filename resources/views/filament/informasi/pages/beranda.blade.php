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

        {{-- Kartu ringkasan "Total Ujian" + breakdown Sempro/Semhas/Sidang,
             sekarang ditaruh SETELAH kartu Jadwal Ujian Mendatang (dulu di
             atasnya, langsung setelah hero). Desain "pill" hasil adaptasi
             dari saran Tailwind (bg-blue-500, rounded-2xl, dst) — class
             utility Tailwind arbitrary/bracket TIDAK dipakai apa adanya
             karena bundle CSS Filament di sini precompiled non-JIT (cuma
             class yang memang dipakai source Filament sendiri yang ada di
             CSS terkompilasi), jadi ditulis ulang jadi CSS biasa dengan
             tampilan yang sama. Ikon FontAwesome (<i class="fas ...">) juga
             diganti emoji karena FontAwesome tidak di-load di panel ini. --}}
        .beranda-stats-pill {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .beranda-stats-pill-total {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-right: 28px;
            border-right: 1px solid #f1f5f9;
        }
        .beranda-stats-pill-icon {
            width: 48px; height: 48px;
            background: var(--dbs-blue-mid);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 6px 14px -4px rgba(37,99,235,.45);
        }
        .beranda-stats-pill-total-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
        .beranda-stats-pill-total-num { font-size: 1.6rem; font-weight: 900; color: #0f172a; line-height: 1; margin-top: 3px; display: flex; align-items: baseline; gap: 5px; }
        .beranda-stats-pill-total-num span { font-size: 11.5px; font-weight: 600; color: #94a3b8; }

        .beranda-stats-pill-grid { flex: 1; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; min-width: 260px; }
        .beranda-stats-pill-item { display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border-radius: 12px; padding: 10px 14px; }
        .beranda-stats-pill-item-left { display: flex; align-items: center; gap: 9px; }
        .beranda-stats-pill-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        {{-- Warna selaras App\Enums\ExamTypeCode (sempro=warning, semhas=info,
             sidang=success) — bukan warna pada saran Tailwind (blue/amber/
             emerald) supaya tetap konsisten dengan badge lain di halaman ini. --}}
        .beranda-stats-pill-dot-sempro { background: #f59e0b; }
        .beranda-stats-pill-dot-semhas { background: #2563eb; }
        .beranda-stats-pill-dot-sidang { background: #10b981; }
        .beranda-stats-pill-item-label { font-size: 13px; font-weight: 700; color: #475569; }
        .beranda-stats-pill-count { font-size: 15px; font-weight: 800; padding: 2px 10px; border-radius: 8px; }
        .beranda-stats-pill-count-sempro { background: #fef3c7; color: #92400e; }
        .beranda-stats-pill-count-semhas { background: #dbeafe; color: #1d4ed8; }
        .beranda-stats-pill-count-sidang { background: #dcfce7; color: #15803d; }

        @media (max-width: 720px) {
            .beranda-stats-pill { flex-direction: column; align-items: stretch; }
            .beranda-stats-pill-total { border-right: none; border-bottom: 1px solid #f1f5f9; padding-right: 0; padding-bottom: 16px; }
            .beranda-stats-pill-grid { grid-template-columns: 1fr; }
        }

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
        {{-- Satu-satunya sumber jarak antar blok kartu (Waktu & Lokasi/
             Header/Tim Penguji) — semuanya berdiri sendiri penuh selebar
             kartu, tidak ada lagi yang sejajar horizontal. --}}
        .jadwal-card-stack { gap: .6rem; }

        {{-- Box abu-abu dipakai Tim Penguji, & label kecil di atasnya juga
             satu sumber (dulu dipakai Judul/Waktu juga — keduanya sekarang
             tanpa box, lihat .jadwal-waktu-bare & .jadwal-judul-toggle). --}}
        .jadwal-box { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: .5rem; padding: .45rem .6rem; }
        .jadwal-group-label { display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #94a3b8; margin-bottom: .25rem; }

        {{-- Waktu & Lokasi — sekarang blok PALING ATAS kartu (sebelum
             Jenis Ujian), tanpa label & tanpa box abu-abu (bare). Class
             exam-waktu-* (bukan jadwal-waktu-item/-icon/-sep) karena
             diemit App\Support\ExamScheduleFormat, dipakai bersama dengan
             kartu admin ExamRegistrationResource. --}}
        .jadwal-waktu-bare { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem; font-size: 10.5px; font-weight: 600; color: #475569; }
        .jadwal-waktu-bare .exam-waktu-icon { font-size: 11px; line-height: 1; margin-right: .15rem; }
        .jadwal-waktu-bare .exam-waktu-sep { color: #cbd5e1; font-weight: 400; }

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

        {{-- Baris 2: Nama + trigger "Judul" (native <details>/<summary>,
             tanpa JS) sejajar di baris yang sama. Judul asli disembunyikan
             sampai trigger diklik, lalu muncul di bawahnya. --}}
        .jadwal-nama-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: .3rem .5rem; margin-top: .25rem; }
        .jadwal-nama { font-size: 1rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .jadwal-judul-toggle summary { list-style: none; cursor: pointer; }
        .jadwal-judul-toggle summary::-webkit-details-marker { display: none; }
        .jadwal-judul-trigger { display: inline; margin-bottom: 0; }
        .jadwal-judul-toggle[open] .jadwal-judul-trigger { color: #64748b; }
        .jadwal-judul-text { margin: .2rem 0 0; font-size: 12px; font-style: italic; color: #475569; line-height: 1.4; }
        .jadwal-judul-empty { color: #cbd5e1; font-style: normal; }

        {{-- Tim Penguji — kolom sendiri di paling bawah, penuh selebar
             kartu, dibungkus .jadwal-box. --}}
        .jadwal-penguji-col { display: flex; flex-direction: column; }

        {{-- List teks polos (tanpa badge/pill), nomor urut 1-5 ditulis
             manual di PHP (bukan <ol> — supaya nomornya tetap sesuai
             posisi slot walau ada yang kosong dilewati, bukan dihitung
             ulang). Warna seragam & tidak bold untuk semua peran (dulu
             beda warna per peran + ketua bold, sekarang cuma dibedakan
             lewat suffix mahkota/"(P1)"/"(P2)", bukan warna/berat huruf).
             Teks bebas wrap alami (tanpa nowrap) jadi nama panjang aman
             tanpa perlu trik overflow/ellipsis lagi. --}}
        .jadwal-penguji-list { display: flex; flex-direction: column; gap: .2rem; }
        .jadwal-penguji-item { font-size: 10.5px; font-weight: 500; line-height: 1.35; color: #475569; }

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

        {{-- Bento grid: 2 kartu kiri-kanan. Kiri = Total + ring persentase
             Lulus (angka "N Sudah Lulus" tanpa % ditaruh langsung di bawah
             label Total Mahasiswa, SEBARIS dengan ring — bukan strip
             terpisah lagi — supaya ring tetap leluasa & kartu tidak
             bertambah tinggi). Kanan = Belum Lulus (% ditulis langsung
             setelah labelnya, bukan baris baru) + donat porsi Belum
             Sempro/Akan Semhas/Akan Sidang menggantikan 3 kartu kecil
             bottleneck. Angka bottleneck tetap dari $all
             (Beranda::rekapSemuaAngkatan()), yaitu SUM seluruh angkatan —
             bukan angka per-angkatan; persentase donat dihitung terhadap
             gabungan ketiganya lewat Beranda::bottleneckShares() supaya
             angka yang digambar (bottleneckDonutStyle()) & yang ditulis di
             legenda selalu sama. --}}
        .beranda-bento-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
        .beranda-bento-card {
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }
        .beranda-bento-card-total { background: #eff6ff; border: 1px solid #dbeafe; }
        .beranda-bento-card-belum-lulus { background: #fffbeb; border: 1px solid #fef3c7; }
        .beranda-bento-num { font-size: 2rem; font-weight: 900; color: #0f172a; line-height: 1; }
        .beranda-bento-label { font-size: 11.5px; color: #64748b; margin-top: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .beranda-bento-sub-inline { font-size: 10.5px; font-weight: 700; text-transform: none; letter-spacing: 0; color: #92400e; margin-left: 4px; }

        {{-- Baris kartu kiri: kolom teks (Total + Sudah Lulus inline) +
             ring persentase Lulus sejajar. --}}
        .beranda-bento-total-main { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        {{-- Baris kartu kanan: angka Belum Lulus + donat bottleneck sejajar
             (bukan ditumpuk vertikal lagi) supaya kartu tidak bertambah
             tinggi, meniru pola .beranda-bento-total-main di kartu kiri. --}}
        .beranda-bento-belum-lulus-main { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .beranda-bento-lulus-inline { font-size: .8rem; font-weight: 700; color: #16a34a; margin-top: 6px; }

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

        {{-- Donat bottleneck: lingkaran conic-gradient 3-porsi + legenda teks
             di sampingnya (dot warna + label + N (pct%)), dipisah garis dari
             angka Belum Lulus di atasnya. --}}
        .beranda-bento-donut-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .beranda-bento-donut { position: relative; width: 84px; height: 84px; border-radius: 50%; flex-shrink: 0; }
        .beranda-bento-donut-inner {
            position: absolute; top: 12px; left: 12px; width: 60px; height: 60px;
            background: #fffbeb; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .beranda-bento-donut-total { font-size: .85rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .beranda-bento-donut-total-label { font-size: 8px; color: #92400e; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; margin-top: 2px; }
        .beranda-bento-donut-legend { flex: 1; min-width: 150px; display: flex; flex-direction: column; gap: 5px; }
        .beranda-bento-donut-legend-item { display: flex; align-items: center; gap: 6px; font-size: 11px; }
        .beranda-bento-donut-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .beranda-bento-donut-dot-sempro { background: #f59e0b; }
        .beranda-bento-donut-dot-semhas { background: #2563eb; }
        .beranda-bento-donut-dot-sidang { background: #10b981; }
        .beranda-bento-donut-legend-label { color: #475569; font-weight: 600; flex: 1; }
        .beranda-bento-donut-legend-value { color: #0f172a; font-weight: 800; }
        .beranda-bento-donut-legend-value small { color: #94a3b8; font-weight: 600; }

        @media (max-width: 720px) {
            .beranda-bento-grid { grid-template-columns: 1fr; }
            .beranda-bento-donut-row { justify-content: center; }
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

    {{-- ═══════ JADWAL UJIAN (card grid, semua ditampilkan) ═══════ --}}
    <div class="beranda-section-heading">
        <h2>Jadwal Ujian Mendatang</h2>
        <p>Setiap kartu menampilkan jenis ujian, mahasiswa, judul, waktu, dan para penguji — diurutkan berdasarkan tanggal &rsaquo; jam &rsaquo; ruang.</p>
    </div>

    {{-- ═══════ STATS (pill) ═══════ --}}
    @php($stats = $this->jadwalStats())
    <div class="beranda-stats-pill">
        <div class="beranda-stats-pill-total">
            <div class="beranda-stats-pill-icon">🗂️</div>
            <div>
                <div class="beranda-stats-pill-total-label">Total Ujian</div>
                <div class="beranda-stats-pill-total-num">{{ $stats['total'] }} <span>Terjadwal</span></div>
            </div>
        </div>
        <div class="beranda-stats-pill-grid">
            <div class="beranda-stats-pill-item">
                <div class="beranda-stats-pill-item-left">
                    <span class="beranda-stats-pill-dot beranda-stats-pill-dot-sempro"></span>
                    <span class="beranda-stats-pill-item-label">Sempro</span>
                </div>
                <span class="beranda-stats-pill-count beranda-stats-pill-count-sempro">{{ $stats['sempro'] }}</span>
            </div>
            <div class="beranda-stats-pill-item">
                <div class="beranda-stats-pill-item-left">
                    <span class="beranda-stats-pill-dot beranda-stats-pill-dot-semhas"></span>
                    <span class="beranda-stats-pill-item-label">Semhas</span>
                </div>
                <span class="beranda-stats-pill-count beranda-stats-pill-count-semhas">{{ $stats['semhas'] }}</span>
            </div>
            <div class="beranda-stats-pill-item">
                <div class="beranda-stats-pill-item-left">
                    <span class="beranda-stats-pill-dot beranda-stats-pill-dot-sidang"></span>
                    <span class="beranda-stats-pill-item-label">Sidang</span>
                </div>
                <span class="beranda-stats-pill-count beranda-stats-pill-count-sidang">{{ $stats['sidang'] }}</span>
            </div>
        </div>
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

        {{-- ═══ Bento grid: 2 kartu kiri-kanan. Kiri = Total + ring % Lulus,
             "N Sudah Lulus" (tanpa %) ditaruh langsung di bawah label Total
             Mahasiswa (sebaris dengan ring, bukan strip terpisah). Kanan =
             Belum Lulus (% inline setelah labelnya) + donat porsi Belum
             Sempro/Akan Semhas/Akan Sidang, semua angka dari SUM seluruh
             angkatan ($all = Beranda::rekapSemuaAngkatan(),
             $shares = Beranda::bottleneckShares($all)). ═══ --}}
        @php($shares = $this->bottleneckShares($all))
        <div class="beranda-bento-grid">
            <div class="beranda-bento-card beranda-bento-card-total">
                <div class="beranda-bento-total-main">
                    <div>
                        <div class="beranda-bento-num">{{ $all['total'] }}</div>
                        <div class="beranda-bento-label">Total Mahasiswa</div>
                        <div class="beranda-bento-lulus-inline">{{ $all['lulus'] }} Sudah Lulus</div>
                    </div>
                    <div class="beranda-bento-ring" style="{{ $this->ringStyle($all['lulus_pct']) }}">
                        <div class="beranda-bento-ring-inner">
                            <span class="beranda-bento-ring-pct">{{ $all['lulus_pct'] }}%</span>
                            <span class="beranda-bento-ring-label">Lulus</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="beranda-bento-card beranda-bento-card-belum-lulus">
                <div class="beranda-bento-belum-lulus-main">
                    <div>
                        <div class="beranda-bento-num">{{ $all['belum_lulus'] }}</div>
                        <div class="beranda-bento-label">Belum Lulus<span class="beranda-bento-sub-inline">{{ $all['belum_lulus_pct'] }}%</span></div>
                    </div>
                    <div class="beranda-bento-donut-row">
                        <div class="beranda-bento-donut" style="{{ $this->bottleneckDonutStyle($shares) }}">
                            <div class="beranda-bento-donut-inner">
                                <span class="beranda-bento-donut-total">{{ $all['belum_sempro'] + $all['akan_semhas'] + $all['akan_sidang'] }}</span>
                                <span class="beranda-bento-donut-total-label">Mhs</span>
                            </div>
                        </div>
                        <div class="beranda-bento-donut-legend">
                            <div class="beranda-bento-donut-legend-item">
                                <span class="beranda-bento-donut-dot beranda-bento-donut-dot-sempro"></span>
                                <span class="beranda-bento-donut-legend-label">Belum Sempro</span>
                                <span class="beranda-bento-donut-legend-value">{{ $shares['sempro']['count'] }} <small>({{ $shares['sempro']['pct'] }}%)</small></span>
                            </div>
                            <div class="beranda-bento-donut-legend-item">
                                <span class="beranda-bento-donut-dot beranda-bento-donut-dot-semhas"></span>
                                <span class="beranda-bento-donut-legend-label">Akan Semhas</span>
                                <span class="beranda-bento-donut-legend-value">{{ $shares['semhas']['count'] }} <small>({{ $shares['semhas']['pct'] }}%)</small></span>
                            </div>
                            <div class="beranda-bento-donut-legend-item">
                                <span class="beranda-bento-donut-dot beranda-bento-donut-dot-sidang"></span>
                                <span class="beranda-bento-donut-legend-label">Akan Sidang</span>
                                <span class="beranda-bento-donut-legend-value">{{ $shares['sidang']['count'] }} <small>({{ $shares['sidang']['pct'] }}%)</small></span>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Belum Sempro</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Akan Semhas</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium text-gray-500">Akan Sidang</th>
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
