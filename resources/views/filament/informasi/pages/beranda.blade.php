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

        {{-- Daftar penguji (Jadwal Ujian): penguji biasa vs pembimbing vs
             ketua dibedakan warna. --}}
        .beranda-penguji-list { display: flex; flex-wrap: wrap; gap: .35rem; }
        .beranda-penguji-badge { display: inline-flex; align-items: center; padding: .2rem .55rem; border-radius: .4rem; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .beranda-penguji-biasa { background: #f1f5f9; color: #475569; }
        .beranda-penguji-pembimbing { background: #e0e7ff; color: #3730a3; }
        .beranda-penguji-ketua { background: #fef3c7; color: #92400e; font-weight: 800; }

        {{-- Rekap: satu Card besar membungkus statistik semua-angkatan +
             card per-angkatan. --}}
        .beranda-rekap-card-outer {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            padding: 24px;
        }
        .beranda-rekap-all-heading { font-size: .8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; }
        .beranda-rekap-all-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 1px;
            background: #e2e8f0;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .beranda-rekap-all-stats .beranda-stat-item { padding: 12px 14px; }
        .beranda-rekap-all-stats .beranda-stat-num { font-size: 1.35rem; }

        .beranda-rekap-per-angkatan-heading { font-size: .8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; }

        .beranda-rekap-card .fi-ta-col-wrp { padding: 1rem 1.25rem; }
        .beranda-rekap-angkatan { font-size: 1rem; font-weight: 800; color: #0f172a; margin-bottom: .5rem; }
        {{-- 3 kolom tetap (bukan auto-fit) supaya baris 1 & baris 2 selaras --}}
        .beranda-rekap-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
        .beranda-rekap-row + .beranda-rekap-row { margin-top: .5rem; }
        .beranda-rekap-metric { text-align: center; padding: .5rem; border-radius: .5rem; background: #f8fafc; }
        .beranda-rekap-metric a { text-decoration: none; color: inherit; display: block; }
        .beranda-rekap-metric-num { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
        .beranda-rekap-metric-pct { font-size: .8rem; font-weight: 700; color: #64748b; }
        .beranda-rekap-metric-label { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-top: 2px; }
        .beranda-rekap-metric-reg { font-size: 10.5px; color: #16a34a; font-weight: 700; margin-top: 2px; }

        @media (max-width: 480px) {
            .beranda-rekap-grid { grid-template-columns: repeat(3, 1fr); gap: .35rem; }
            .beranda-rekap-metric { padding: .35rem; }
        }
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
        <p>Diurutkan berdasarkan tanggal &rsaquo; jam &rsaquo; ruang — seluruh jadwal ditampilkan, tidak dibatasi tinggi layar.</p>
    </div>
    <div data-grid-fit="none" class="mb-8">
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
        <div class="beranda-rekap-all-stats">
            <div class="beranda-stat-item">
                <div class="beranda-stat-num">{{ $all['total'] }}</div>
                <div class="beranda-stat-label">Total</div>
            </div>
            <div class="beranda-stat-item">
                <div class="beranda-stat-num">{{ $all['lulus'] }} <span class="beranda-rekap-metric-pct">({{ $all['lulus_pct'] }}%)</span></div>
                <div class="beranda-stat-label">Lulus</div>
            </div>
            <div class="beranda-stat-item">
                <div class="beranda-stat-num">{{ $all['belum_lulus'] }} <span class="beranda-rekap-metric-pct">({{ $all['belum_lulus_pct'] }}%)</span></div>
                <div class="beranda-stat-label">Belum Lulus</div>
            </div>
            <div class="beranda-stat-item">
                <div class="beranda-stat-num">{{ $all['belum_sempro'] }}</div>
                <div class="beranda-stat-label">Belum Sempro</div>
            </div>
            <div class="beranda-stat-item">
                <div class="beranda-stat-num">{{ $all['akan_semhas'] }}</div>
                <div class="beranda-stat-label">Akan Semhas</div>
            </div>
            <div class="beranda-stat-item">
                <div class="beranda-stat-num">{{ $all['akan_sidang'] }}</div>
                <div class="beranda-stat-label">Akan Sidang</div>
            </div>
        </div>

        <div class="beranda-rekap-per-angkatan-heading">Per Angkatan</div>
        {{-- Card grid ditulis manual (bukan Filament Table) karena datanya array
             hasil agregasi ($this->rekap()), bukan Eloquent — kelas fi-ta-record/
             fi-ta-content-grid dipakai persis sama seperti Filament sendiri
             (lihat nuir-proposal-overview.blade.php untuk pola yang sama). --}}
        <div class="fi-ta-content-grid gap-4 p-0" data-grid-fit="none">
            @forelse ($this->rekap() as $row)
                <div class="fi-ta-record beranda-rekap-card relative h-full rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 transition duration-75 dark:bg-white/5 dark:ring-white/10">
                    <div class="fi-ta-col-wrp">
                        <div class="beranda-rekap-angkatan">Angkatan {{ $row['angkatan'] }}</div>

                        {{-- Baris 1: Total, Lulus, Belum Lulus (persentase dari Total) --}}
                        <div class="beranda-rekap-grid beranda-rekap-row">
                            <div class="beranda-rekap-metric">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Total Mahasiswa']) }}">
                                    <div class="beranda-rekap-metric-num">{{ $row['total'] }}</div>
                                    <div class="beranda-rekap-metric-label">Total</div>
                                </a>
                            </div>
                            <div class="beranda-rekap-metric">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Lulus']) }}">
                                    <div class="beranda-rekap-metric-num">{{ $row['lulus'] }} <span class="beranda-rekap-metric-pct">({{ $row['lulus_pct'] }}%)</span></div>
                                    <div class="beranda-rekap-metric-label">Lulus</div>
                                </a>
                            </div>
                            <div class="beranda-rekap-metric">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Belum Lulus']) }}">
                                    <div class="beranda-rekap-metric-num">{{ $row['belum_lulus'] }} <span class="beranda-rekap-metric-pct">({{ $row['belum_lulus_pct'] }}%)</span></div>
                                    <div class="beranda-rekap-metric-label">Belum Lulus</div>
                                </a>
                            </div>
                        </div>

                        {{-- Baris 2: Belum Sempro, Akan Semhas, Akan Sidang --}}
                        <div class="beranda-rekap-grid beranda-rekap-row">
                            <div class="beranda-rekap-metric">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Belum Sempro']) }}">
                                    <div class="beranda-rekap-metric-num">{{ $row['belum_sempro'] }}</div>
                                    <div class="beranda-rekap-metric-label">Belum Sempro</div>
                                    <div class="beranda-rekap-metric-reg">{{ $row['belum_sempro_reg'] }} reg</div>
                                </a>
                            </div>
                            <div class="beranda-rekap-metric">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Akan Semhas']) }}">
                                    <div class="beranda-rekap-metric-num">{{ $row['akan_semhas'] }}</div>
                                    <div class="beranda-rekap-metric-label">Akan Semhas</div>
                                    <div class="beranda-rekap-metric-reg">{{ $row['akan_semhas_reg'] }} reg</div>
                                </a>
                            </div>
                            <div class="beranda-rekap-metric">
                                <a href="{{ \App\Filament\Informasi\Pages\RecapList::getUrl(['generation' => $row['angkatan'], 'context' => 'Mahasiswa Akan Sidang']) }}">
                                    <div class="beranda-rekap-metric-num">{{ $row['akan_sidang'] }}</div>
                                    <div class="beranda-rekap-metric-label">Akan Sidang</div>
                                    <div class="beranda-rekap-metric-reg">{{ $row['akan_sidang_reg'] }} reg</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="col-span-full px-2 py-3 text-gray-500">Belum ada data angkatan.</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
