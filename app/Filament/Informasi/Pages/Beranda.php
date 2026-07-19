<?php

namespace App\Filament\Informasi\Pages;

use App\Enums\ExamTypeCode;
use App\Models\ExamRegistration;
use App\Models\GuideExaminer;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Versi Filament (publik, tanpa login) dari App\Http\Controllers\
 * WelcomeController + resources/views/welcome.blade.php — halaman ini jadi
 * beranda situs (routes/web.php Route::get('/', ...) diarahkan ke sini,
 * lihat App\Support\FilamentBrand & InformasiPanelProvider::homeUrl()).
 */
class Beranda extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.informasi.pages.beranda';

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'informasi', $tenant);
    }

    public function getTitle(): string|Htmlable
    {
        return config('app.name', 'DBS').' — Dewan Bimbingan Skripsi';
    }

    /**
     * Judul di atas ($this->getTitle()) sudah cukup untuk tab browser —
     * tanpa override ini, BasePage::getHeading() jatuh balik ke getTitle()
     * dan merender heading yang sama persis lagi di atas hero section
     * (redundan, lihat beranda.blade.php).
     */
    public function getHeading(): string
    {
        return '';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->jadwalUjianQuery())
            ->contentGrid([
                'default' => 1,
            ])
            ->columns([
                // Dibungkus Layout\Stack — ini yang membuat Filament benar-benar
                // merender tiap baris sebagai card (hasColumnsLayout()), bukan
                // cuma ->contentGrid() saja. Tiap kolom HTML utuh
                // (getStateUsing()+html(), bukan ->prefix()) supaya labelnya
                // bisa diberi warna berbeda dari isinya. Semua kolom berdiri
                // sendiri penuh selebar kartu (bukan sejajar horizontal lagi):
                // waktu & lokasi PALING ATAS (sebelum jenis ujian), lalu
                // header (badge jenis ujian+NIM, nama + trigger "Judul"),
                // lalu tim penguji paling bawah. Judul tugas akhir sendiri
                // digabung ke kolom header (buildJadwalHeaderHtml), bukan
                // kolom terpisah lagi — disembunyikan di balik trigger
                // <details>/<summary> di sebelah nama. ->space() diberi nama
                // class custom (bukan preset 1/2/3) — lihat HasSpace::space(),
                // default branch di stack.blade.php cuma echo string apa
                // adanya sebagai class tambahan.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('waktu')
                        ->label('Waktu & Lokasi')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildWaktuHtml($record))
                        ->html(),
                    Tables\Columns\TextColumn::make('header')
                        ->label('Mahasiswa')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildJadwalHeaderHtml($record))
                        ->html(),
                    Tables\Columns\TextColumn::make('penguji')
                        ->label('Tim Penguji')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildPengujiHierarchyHtml($record))
                        ->html(),
                ])->space('jadwal-card-stack'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada jadwal ujian mendatang')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    /**
     * Baris 1: badge Jenis Ujian + badge NIM sejajar horizontal. Baris 2:
     * Nama Mahasiswa + trigger "Judul" (native <details>/<summary>, tanpa
     * JS) sejajar di baris yang sama — judul tugas akhir sendiri
     * disembunyikan sampai trigger diklik (lihat buildJudulToggleHtml()).
     * Semua digabung satu kolom HTML (bukan kolom Filament terpisah)
     * supaya badge tetap sejajar meski nama sangat panjang (lihat
     * .jadwal-nama di beranda.blade.php).
     */
    private function buildJadwalHeaderHtml(ExamRegistration $record): string
    {
        $type = ExamTypeCode::tryFrom($record->exam_type_id);
        $jenis = $type?->label() ?? $record->examtype?->name ?? '—';
        $jenisClass = 'jadwal-badge-jenis-'.($type ? strtolower($type->name) : 'default');
        $jenisEmoji = $type ? $type->emoji().' ' : '';
        $nim = $record->student?->username ?? '—';
        $nama = $record->student?->name ?? '—';

        return '<div class="jadwal-badge-row">'
            .'<span class="jadwal-badge '.$jenisClass.'">'.$jenisEmoji.e($jenis).'</span>'
            .'<span class="jadwal-badge jadwal-badge-nim">'.e($nim).'</span>'
            .'</div>'
            .'<div class="jadwal-nama-row">'
            .'<span class="jadwal-nama">'.e($nama).'</span>'
            .$this->buildJudulToggleHtml($record)
            .'</div>';
    }

    /**
     * Trigger "Judul" bergaya label (.jadwal-group-label, sama seperti
     * label lain di kartu ini) yang membuka/menutup judul tugas akhir —
     * pakai <details>/<summary> native HTML (bukan Alpine/Livewire, karena
     * ini dirender lewat TextColumn::html() yang disaring
     * Str::sanitizeHtml(); atribut non-standar semacam x-data kemungkinan
     * disaring, sedangkan <details>/<summary> ada di daftar elemen aman
     * Symfony HtmlSanitizer jadi lolos apa adanya).
     */
    private function buildJudulToggleHtml(ExamRegistration $record): string
    {
        $judul = $record->title;

        $value = filled($judul)
            ? e($judul)
            : '<span class="jadwal-judul-empty">—</span>';

        return '<details class="jadwal-judul-toggle">'
            .'<summary class="jadwal-group-label jadwal-judul-trigger">Judul</summary>'
            .'<p class="jadwal-judul-text">'.$value.'</p>'
            .'</details>';
    }

    /**
     * Waktu & Lokasi sekarang blok PALING ATAS kartu (sebelum Jenis
     * Ujian), tanpa label & tanpa box abu-abu (bare). Format inti
     * (tanggal/jam/ruang satu baris, dipisah "|", ruang berprefix "Ruang")
     * dari App\Support\ExamScheduleFormat — dipakai bersama dengan
     * ExamRegistrationResource::getCardColumns() (kartu admin + widget
     * dashboard) supaya formatnya selalu identik di kedua tempat.
     */
    private function buildWaktuHtml(ExamRegistration $record): string
    {
        return '<div class="jadwal-waktu-bare">'
            .\App\Support\ExamScheduleFormat::inlineHtml($record->exam_date, $record->exam_time, $record->room)
            .'</div>';
    }

    /**
     * Daftar penguji tanpa badge — list teks polos, nomor urut tetap 1-5
     * mengikuti posisi slot (Penguji 1-3 = 1-3, Pembimbing 1-2 = 4-5),
     * warna & berat huruf SERAGAM untuk semua peran (tidak bold, tidak
     * dibedakan biru/hijau lagi) — ketua/pembimbing tetap bisa dikenali
     * lewat suffix mahkota/"(P1)"/"(P2)", bukan lewat warna. Ketua
     * (chief_id, di slot manapun) ditandai emoji mahkota di AKHIR baris,
     * bukan badge terpisah — kalau ketua kebetulan salah satu pembimbing,
     * baris itu dapat mahkota SEKALIGUS suffix "(P1)"/"(P2)" (tidak
     * ditampilkan dua kali di baris lain).
     */
    private function buildPengujiHierarchyHtml(ExamRegistration $record): string
    {
        $slots = [
            ['label' => 'Penguji 1', 'id' => $record->examiner1_id, 'name' => $record->examiner1?->name, 'kind' => 'penguji'],
            ['label' => 'Penguji 2', 'id' => $record->examiner2_id, 'name' => $record->examiner2?->name, 'kind' => 'penguji'],
            ['label' => 'Penguji 3', 'id' => $record->examiner3_id, 'name' => $record->examiner3?->name, 'kind' => 'penguji'],
            ['label' => 'Pembimbing 1', 'id' => $record->guide1_id, 'name' => $record->guide1?->name, 'kind' => 'pembimbing'],
            ['label' => 'Pembimbing 2', 'id' => $record->guide2_id, 'name' => $record->guide2?->name, 'kind' => 'pembimbing'],
        ];

        $items = '';

        foreach ($slots as $i => $slot) {
            if (blank($slot['id'])) {
                continue;
            }

            $urutan = $i + 1;
            $isChief = $record->chief_id && (int) $slot['id'] === (int) $record->chief_id;
            $name = $slot['name'] ?? '(?)';

            $suffix = '';

            if ($slot['kind'] === 'pembimbing') {
                $kode = str_replace('Pembimbing ', 'P', $slot['label']); // "P1" / "P2"
                $suffix .= ' ('.e($kode).')';
            }

            if ($isChief) {
                $suffix .= ' &#128081;';
            }

            $items .= '<div class="jadwal-penguji-item">'.$urutan.'. '.e($name).$suffix.'</div>';
        }

        if ($items === '') {
            $items = '<span class="jadwal-judul-empty">—</span>';
        }

        return '<div class="jadwal-penguji-col jadwal-box">'
            .'<span class="jadwal-group-label">Tim Penguji:</span>'
            .'<div class="jadwal-penguji-list">'.$items.'</div>'
            .'</div>';
    }

    private function jadwalUjianQuery(): Builder
    {
        $now = Carbon::now();
        $tanggalSekarang = $now->toDateString();
        $waktuSelesai = $now->copy()->subHour()->format('H:i:s');

        return ExamRegistration::query()
            ->with(['student', 'examtype', 'examiner1', 'examiner2', 'examiner3', 'guide1', 'guide2', 'examScores.lecture'])
            ->where(function (Builder $query) use ($tanggalSekarang, $waktuSelesai) {
                $query->where('exam_date', '>', $tanggalSekarang)
                    ->orWhere(function (Builder $query) use ($tanggalSekarang, $waktuSelesai) {
                        $query->where('exam_date', '=', $tanggalSekarang)
                            ->where('exam_time', '>=', $waktuSelesai);
                    });
            })
            ->orderBy('exam_date')
            ->orderBy('exam_time')
            ->orderBy('room');
    }

    /**
     * @return array{total: int, sempro: int, semhas: int, sidang: int}
     */
    public function jadwalStats(): array
    {
        $counts = $this->jadwalUjianQuery()->get(['exam_type_id']);

        return [
            'total' => $counts->count(),
            'sempro' => $counts->where('exam_type_id', 1)->count(),
            'semhas' => $counts->where('exam_type_id', 2)->count(),
            'sidang' => $counts->where('exam_type_id', 3)->count(),
        ];
    }

    /**
     * Disalin dari WelcomeController::index() (sudah dihapus, lihat git
     * history commit 54be7b4) — satu baris rekap per angkatan, dipakai
     * kartu "Rekap Kelulusan & Ujian Skripsi" di beranda.blade.php, tautan
     * tiap angka mengarah ke RecapList.
     *
     * PENTING: proposal_date/seminar_date/thesis_date di guide_examiners
     * di-stample begitu ExamRegistration didaftarkan (lihat
     * ExamRegistrationController::store()), SEBELUM ujian berlangsung/
     * dinilai — jadi "tanggal terisi" TIDAK sama dengan "sudah lulus tahap
     * itu" di KETIGA tahap (sempro/semhas/sidang), bukan cuma sidang.
     * Konfirmasi lulus baru terjadi belakangan & terpisah, lewat
     * ViewChiefExam (set pass_exam=true di ExamRegistration), tidak pernah
     * menyentuh tanggal di guide_examiners.
     *
     * Aturan kategori (persis empat kelompok yang saling lepas & menutup
     * seluruh Total lewat teleskop — lihat RecapList::buildQuery() untuk
     * filter list-nya, harus sama persis):
     * - Lulus: trulyPassedIds('thesis_date', examTypeId: 3).
     * - Belum Lulus: Total - Lulus.
     * - Akan Sidang: trulyPassedIds('seminar_date', 2) MINUS Lulus.
     * - Akan Semhas: trulyPassedIds('proposal_date', 1) MINUS trulyPassedIds('seminar_date', 2).
     * - Belum Sempro: semua user di angkatan itu MINUS trulyPassedIds('proposal_date', 1).
     * "trulyPassed" = tanggal terisi DAN tidak ter-disqualify (lihat
     * stageDisqualifiedIds()) — exclusion-based (anggap lulus KECUALI ada
     * bukti sebaliknya), bukan positive-requirement, supaya ExamRegistration
     * yang terhapus atau tanggal yang diisi manual lewat GuideExaminerResource
     * tidak salah menggugurkan status lulus.
     * "* reg" = di antara kelompok itu, berapa yang SUDAH terdaftar di
     * exam_registrations untuk jenis ujian berikutnya tapi pass_exam belum 1
     * (murni angka tambahan, tidak memindahkan mahasiswa ke kelompok lain).
     *
     * @return Collection<int, array{angkatan: int|string, total: int, lulus: int, lulus_pct: float, belum_lulus: int, belum_lulus_pct: float, belum_sempro: int, belum_sempro_reg: int, akan_semhas: int, akan_semhas_reg: int, akan_sidang: int, akan_sidang_reg: int}>
     */
    public function rekap(): Collection
    {
        $angkatans = GuideExaminer::where('year_generation', '>=', 2019)
            ->distinct()
            ->orderBy('year_generation')
            ->pluck('year_generation');

        return $angkatans->map(function ($angkatan) {
            $total = GuideExaminer::where('year_generation', $angkatan)->count();
            $allIds = GuideExaminer::where('year_generation', $angkatan)->pluck('user_id');

            $trulyPassedSemproIds = $this->trulyPassedIds($angkatan, 'proposal_date', 1);
            $trulyPassedSemhasIds = $this->trulyPassedIds($angkatan, 'seminar_date', 2);
            $lulusIds = $this->trulyPassedIds($angkatan, 'thesis_date', 3);

            $lulus = $lulusIds->count();
            $belumLulus = $total - $lulus;

            $belumSemproIds = $allIds->diff($trulyPassedSemproIds)->values();
            $akanSemhasIds = $trulyPassedSemproIds->diff($trulyPassedSemhasIds)->values();
            $akanSidangIds = $trulyPassedSemhasIds->diff($lulusIds)->values();

            return [
                'angkatan' => $angkatan,
                'total' => $total,
                'lulus' => $lulus,
                'lulus_pct' => $total > 0 ? round($lulus / $total * 100, 1) : 0.0,
                'belum_lulus' => $belumLulus,
                'belum_lulus_pct' => $total > 0 ? round($belumLulus / $total * 100, 1) : 0.0,
                'belum_sempro' => $belumSemproIds->count(),
                'belum_sempro_reg' => $this->countUpcomingRegistrations($belumSemproIds, examTypeId: 1),
                'akan_semhas' => $akanSemhasIds->count(),
                'akan_semhas_reg' => $this->countUpcomingRegistrations($akanSemhasIds, examTypeId: 2),
                'akan_sidang' => $akanSidangIds->count(),
                'akan_sidang_reg' => $this->countUpcomingRegistrations($akanSidangIds, examTypeId: 3),
            ];
        });
    }

    /**
     * Total gabungan seluruh angkatan dari rekap() — ditampilkan sebagai
     * item statistik ringkas terpisah dari card per-angkatan.
     *
     * @return array{total: int, lulus: int, lulus_pct: float, belum_lulus: int, belum_lulus_pct: float, belum_sempro: int, akan_semhas: int, akan_sidang: int}
     */
    public function rekapSemuaAngkatan(): array
    {
        $rows = $this->rekap();

        $total = (int) $rows->sum('total');
        $lulus = (int) $rows->sum('lulus');
        $belumLulus = (int) $rows->sum('belum_lulus');

        return [
            'total' => $total,
            'lulus' => $lulus,
            'lulus_pct' => $total > 0 ? round($lulus / $total * 100, 1) : 0.0,
            'belum_lulus' => $belumLulus,
            'belum_lulus_pct' => $total > 0 ? round($belumLulus / $total * 100, 1) : 0.0,
            'belum_sempro' => (int) $rows->sum('belum_sempro'),
            'akan_semhas' => (int) $rows->sum('akan_semhas'),
            'akan_sidang' => (int) $rows->sum('akan_sidang'),
        ];
    }

    /**
     * String CSS conic-gradient() untuk ring persentase di kartu bento
     * "Rekap Kelulusan" (beranda.blade.php) — tanpa SVG/Alpine, ring dibuat
     * murni dari dua lingkaran bertumpuk (lihat .beranda-bento-ring{,-inner}).
     */
    public function ringStyle(float $pct, string $color = '#16a34a', string $track = '#e2e8f0'): string
    {
        $pct = max(0, min(100, $pct));
        $pctStr = rtrim(rtrim(number_format($pct, 1, '.', ''), '0'), '.');

        return "background: conic-gradient({$color} {$pctStr}%, {$track} 0)";
    }

    /**
     * Porsi Belum Sempro/Akan Semhas/Akan Sidang dari $all
     * (rekapSemuaAngkatan()), persentase dihitung terhadap GABUNGAN
     * ketiganya (bukan terhadap Total Mahasiswa) — dipakai bareng oleh
     * donutStyle() (gradient) & legenda teks di kartu bento "Belum Lulus"
     * supaya angka yang digambar & ditulis tidak pernah beda.
     *
     * @param  array{belum_sempro: int, akan_semhas: int, akan_sidang: int}  $all
     * @return array{sempro: array{count: int, pct: float}, semhas: array{count: int, pct: float}, sidang: array{count: int, pct: float}}
     */
    public function bottleneckShares(array $all): array
    {
        $total = $all['belum_sempro'] + $all['akan_semhas'] + $all['akan_sidang'];

        $pct = fn (int $count): float => $total > 0 ? round($count / $total * 100, 1) : 0.0;

        return [
            'sempro' => ['count' => $all['belum_sempro'], 'pct' => $pct($all['belum_sempro'])],
            'semhas' => ['count' => $all['akan_semhas'], 'pct' => $pct($all['akan_semhas'])],
            'sidang' => ['count' => $all['akan_sidang'], 'pct' => $pct($all['akan_sidang'])],
        ];
    }

    /**
     * String CSS conic-gradient() 3-porsi (hard-stop, teknik yang sama
     * seperti ringStyle() tapi diulang per-slice) untuk donat bottleneck —
     * warna selaras dot di .beranda-stats-pill (sempro=amber, semhas=biru,
     * sidang=hijau).
     *
     * @param  array{sempro: array{count: int, pct: float}, semhas: array{count: int, pct: float}, sidang: array{count: int, pct: float}}  $shares
     */
    public function bottleneckDonutStyle(array $shares): string
    {
        if (($shares['sempro']['count'] + $shares['semhas']['count'] + $shares['sidang']['count']) <= 0) {
            return 'background: #e2e8f0';
        }

        $stop1 = $shares['sempro']['pct'];
        $stop2 = $stop1 + $shares['semhas']['pct'];

        return "background: conic-gradient(#f59e0b 0%, #f59e0b {$stop1}%, #2563eb {$stop1}%, #2563eb {$stop2}%, #10b981 {$stop2}%, #10b981 100%)";
    }

    /**
     * user_id di antara $dateFilledIds yang punya ExamRegistration
     * $examTypeId MASIH PENDING: pass_exam=0 DAN tanggal ujiannya BELUM
     * LEWAT (hari ini atau setelahnya) — tanggal di guide_examiners cuma
     * tanda "sudah dijadwalkan" (di-stample saat
     * ExamRegistrationController::store(), sebelum ujian/nilai keluar),
     * bukan "sudah lulus".
     *
     * Rumus persis (jangan diubah tanpa alasan kuat — sudah dikonfirmasi
     * eksplisit): "Sudah Lulus" = banyak thesis_date terisi untuk angkatan
     * itu DIKURANGI banyak ExamRegistration exam_type_id=3 yang masih
     * pass_exam=0 DENGAN exam_date belum lewat. TIDAK ada pengecualian
     * untuk retake (kalau ada baris pass_exam=0 & exam_date belum lewat
     * yang tersisa, user itu TETAP dikurangi dari Lulus, WALAUPUN ada
     * baris pass_exam=1 lain untuk exam_type yang sama) — bukan bug, ini
     * permintaan eksplisit supaya rumusnya konsisten & mudah ditelusuri.
     * Kolom pass_exam cuma berisi 0 (belum lulus) atau 1 (lulus) — TIDAK
     * PERNAH benar-benar NULL di database (sempat salah duga sebelumnya,
     * sudah dikonfirmasi ulang oleh user). ExamRegistration pass_exam=0
     * yang tanggal ujiannya SUDAH LEWAT sengaja TIDAK dipakai sebagai
     * penambah Belum Sempro/Akan Semhas/Akan Sidang lagi (dikonfirmasi
     * eksplisit) — begitu tanggal ujian lewat, baris pass_exam=0 lama
     * dianggap kedaluwarsa/akan digantikan pendaftaran baru, bukan alasan
     * terus menggugurkan status.
     *
     * CATATAN: kriteria ini sekarang identik dengan anotasi "N reg" di
     * tabel (lihat countUpcomingRegistrations()) — pass_exam=0 DAN tanggal
     * ujian belum lewat, sama persis.
     *
     * @param  Collection<int, int>  $dateFilledIds
     * @return Collection<int, int>
     */
    private function stageDisqualifiedIds(Collection $dateFilledIds, int $examTypeId): Collection
    {
        if ($dateFilledIds->isEmpty()) {
            return collect();
        }

        return ExamRegistration::whereIn('user_id', $dateFilledIds)
            ->where('exam_type_id', $examTypeId)
            ->where('pass_exam', 0)
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->pluck('user_id')
            ->unique()
            ->values();
    }

    /**
     * user_id angkatan $angkatan yang BENAR-BENAR lulus tahap $examTypeId:
     * $dateColumn di guide_examiners terisi DAN tidak ter-disqualify (lihat
     * stageDisqualifiedIds()) — persis "count(dateColumn terisi) minus
     * count(ExamRegistration $examTypeId pass_exam=0)".
     *
     * @return Collection<int, int>
     */
    private function trulyPassedIds(int|string $angkatan, string $dateColumn, int $examTypeId): Collection
    {
        $dateFilledIds = GuideExaminer::where('year_generation', $angkatan)
            ->whereNotNull($dateColumn)
            ->pluck('user_id');

        if ($dateFilledIds->isEmpty()) {
            return collect();
        }

        return $dateFilledIds->diff($this->stageDisqualifiedIds($dateFilledIds, $examTypeId))->values();
    }

    /**
     * Di antara $userIds, berapa yang punya ExamRegistration exam_type_id
     * $examTypeId dengan pass_exam MASIH 0 DAN exam_date MASIH AKAN DATANG
     * (hari ini atau setelahnya) — "sudah registrasi tapi belum
     * diujiankan". Kedua syarat wajib sekaligus: begitu pass_exam jadi 1,
     * user itu otomatis lepas dari bucket (lewat stageDisqualifiedIds())
     * DAN dari hitungan reg ini. $userIds sendiri sudah scoped ke anggota
     * bucket (belum sempro/akan semhas/akan sidang) lewat
     * stageDisqualifiedIds() yang juga pakai pass_exam=0 — anotasi "N reg"
     * ini murni informasi tambahan di tabel, tidak memengaruhi angka
     * Total/Lulus/Belum Sempro/dst sama sekali.
     *
     * @param  Collection<int, int>  $userIds
     */
    private function countUpcomingRegistrations(Collection $userIds, int $examTypeId): int
    {
        if ($userIds->isEmpty()) {
            return 0;
        }

        return ExamRegistration::whereIn('user_id', $userIds)
            ->where('exam_type_id', $examTypeId)
            ->where('pass_exam', 0)
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->distinct('user_id')
            ->count('user_id');
    }
}
