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
                // cuma ->contentGrid() saja. 4 kolom, masing-masing HTML utuh
                // (getStateUsing()+html(), bukan ->prefix()) supaya labelnya
                // bisa diberi warna berbeda dari isinya. Semua kolom berdiri
                // sendiri penuh selebar kartu (bukan sejajar horizontal lagi):
                // header, judul, waktu & lokasi, lalu tim penguji paling
                // bawah. ->space() diberi nama class custom (bukan preset
                // 1/2/3) — lihat HasSpace::space(), default branch di
                // stack.blade.php cuma echo string apa adanya sebagai class
                // tambahan.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('header')
                        ->label('Mahasiswa')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildJadwalHeaderHtml($record))
                        ->html(),
                    Tables\Columns\TextColumn::make('judul')
                        ->label('Judul')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildJudulHtml($record))
                        ->html(),
                    Tables\Columns\TextColumn::make('waktu')
                        ->label('Waktu & Lokasi')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildWaktuHtml($record))
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
     * Nama Mahasiswa di bawahnya — digabung satu kolom HTML (bukan 3 kolom
     * Filament terpisah) supaya badge tetap sejajar meski nama sangat
     * panjang (lihat .jadwal-nama di beranda.blade.php).
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
            .'<div class="jadwal-nama">'.e($nama).'</div>';
    }

    private function buildJudulHtml(ExamRegistration $record): string
    {
        $judul = $record->title;

        $value = filled($judul)
            ? '<p class="jadwal-judul-text">'.e($judul).'</p>'
            : '<p class="jadwal-judul-text jadwal-judul-empty">—</p>';

        return '<div class="jadwal-judul jadwal-box"><span class="jadwal-group-label">Judul Tugas Akhir:</span>'.$value.'</div>';
    }

    /**
     * Waktu & Lokasi kolom sendiri (bukan sebaris dengan Tim Penguji lagi).
     * Format inti (tanggal/jam/ruang satu baris, dipisah "|", ruang
     * berprefix "Ruang") dari App\Support\ExamScheduleFormat — dipakai
     * bersama dengan ExamRegistrationResource::getCardColumns() (kartu
     * admin + widget dashboard) supaya formatnya selalu identik di kedua
     * tempat. Box+label di sini murni chrome milik Beranda, tidak ikut
     * dipakai di sisi admin.
     */
    private function buildWaktuHtml(ExamRegistration $record): string
    {
        return '<div class="jadwal-waktu-col jadwal-box">'
            .'<span class="jadwal-group-label">Waktu &amp; Lokasi:</span>'
            .'<div class="jadwal-waktu-line">'
            .\App\Support\ExamScheduleFormat::inlineHtml($record->exam_date, $record->exam_time, $record->room)
            .'</div>'
            .'</div>';
    }

    /**
     * Daftar penguji tanpa badge — list teks polos, nomor urut tetap 1-5
     * mengikuti posisi slot (Penguji 1-3 = 1-3, Pembimbing 1-2 = 4-5),
     * warna & ukuran font mengikuti peran (biru=ketua, hijau=pembimbing,
     * abu²=penguji biasa). Ketua (chief_id, di slot manapun) ditandai
     * emoji mahkota di AKHIR baris, bukan badge terpisah — kalau ketua
     * kebetulan salah satu pembimbing, baris itu dapat mahkota SEKALIGUS
     * suffix "(P1)"/"(P2)" (tidak ditampilkan dua kali di baris lain).
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

            $cssClass = $isChief
                ? 'jadwal-penguji-item-ketua'
                : ($slot['kind'] === 'pembimbing' ? 'jadwal-penguji-item-pembimbing' : 'jadwal-penguji-item-penguji');

            $items .= '<div class="jadwal-penguji-item '.$cssClass.'">'.$urutan.'. '.e($name).$suffix.'</div>';
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
                'belum_sempro_reg' => $this->countRegisteredNotPassed($belumSemproIds, examTypeId: 1),
                'akan_semhas' => $akanSemhasIds->count(),
                'akan_semhas_reg' => $this->countRegisteredNotPassed($akanSemhasIds, examTypeId: 2),
                'akan_sidang' => $akanSidangIds->count(),
                'akan_sidang_reg' => $this->countRegisteredNotPassed($akanSidangIds, examTypeId: 3),
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
     * user_id di antara $dateFilledIds yang punya ExamRegistration
     * $examTypeId MASIH PENDING (pass_exam IS NULL) — tanggal di
     * guide_examiners cuma tanda "sudah dijadwalkan" (di-stample saat
     * ExamRegistrationController::store(), sebelum ujian/nilai keluar),
     * bukan "sudah lulus".
     *
     * Rumus persis (jangan diubah tanpa alasan kuat — sudah dikonfirmasi
     * eksplisit): "Sudah Lulus" = banyak thesis_date terisi untuk angkatan
     * itu DIKURANGI banyak ExamRegistration exam_type_id=3 yang masih
     * pass_exam=NULL. TIDAK ada pengecualian untuk retake (kalau ada baris
     * pass_exam=NULL yang tersisa, user itu TETAP dikurangi dari Lulus,
     * WALAUPUN ada baris pass_exam=1 lain untuk exam_type yang sama) —
     * bukan bug, ini permintaan eksplisit supaya rumusnya konsisten &
     * mudah ditelusuri: selama ada ExamRegistration pass_exam=NULL untuk
     * exam_type_id terkait, dia HARUS ikut mengurangi/menambah hitungan
     * "reg" di kategori yang sesuai. pass_exam=0 SENGAJA tidak dipakai
     * sama sekali (lihat juga countRegisteredNotPassed()) — cuma
     * pass_exam=1 & pass_exam=NULL yang relevan.
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
            ->whereNull('pass_exam')
            ->pluck('user_id')
            ->unique()
            ->values();
    }

    /**
     * user_id angkatan $angkatan yang BENAR-BENAR lulus tahap $examTypeId:
     * $dateColumn di guide_examiners terisi DAN tidak ter-disqualify (lihat
     * stageDisqualifiedIds()) — persis "count(dateColumn terisi) minus
     * count(ExamRegistration $examTypeId pass_exam IS NULL)".
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
     * $examTypeId dengan pass_exam IS NULL (masih pending) — "sudah
     * mendaftar tapi belum dinilai". pass_exam=0 sengaja tidak dihitung di
     * sini (lihat catatan di stageDisqualifiedIds()).
     *
     * @param  Collection<int, int>  $userIds
     */
    private function countRegisteredNotPassed(Collection $userIds, int $examTypeId): int
    {
        if ($userIds->isEmpty()) {
            return 0;
        }

        return ExamRegistration::whereIn('user_id', $userIds)
            ->where('exam_type_id', $examTypeId)
            ->whereNull('pass_exam')
            ->distinct('user_id')
            ->count('user_id');
    }
}
