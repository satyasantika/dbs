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
                // cuma ->contentGrid() saja. 3 kolom, masing-masing HTML utuh
                // (getStateUsing()+html(), bukan ->prefix()) karena badge+nama
                // harus satu blok dan waktu+penguji harus satu baris flex
                // bersama — Layout\Stack cuma bisa menumpuk vertikal antar
                // kolom, jadi elemen yang perlu sejajar horizontal wajib
                // digabung jadi satu kolom HTML. ->space() diberi nama class
                // custom (bukan preset 1/2/3) — lihat HasSpace::space(),
                // default branch di stack.blade.php cuma echo string apa
                // adanya sebagai class tambahan.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('header')
                        ->label('Mahasiswa')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildJadwalHeaderHtml($record))
                        ->html(),
                    Tables\Columns\TextColumn::make('judul')
                        ->label('Judul')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildJudulHtml($record))
                        ->html(),
                    Tables\Columns\TextColumn::make('waktu_dan_penguji')
                        ->label('Waktu & Penguji')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildWaktuDanPengujiHtml($record))
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
     * Satu baris flex asimetris: kolom Waktu (sempit, tak melar) + kolom
     * Penguji (melar, sisa ruang) — harus satu kolom HTML gabungan supaya
     * keduanya bisa sejajar dalam satu baris flex (lihat .jadwal-body-row).
     */
    private function buildWaktuDanPengujiHtml(ExamRegistration $record): string
    {
        $tanggal = $record->exam_date?->translatedFormat('d M Y') ?? '—';
        $jam = $record->exam_time ? Carbon::parse($record->exam_time)->format('H:i') : '—';
        $ruang = $record->room ?: '—';

        // Emoji, bukan <svg> heroicon — Filament men-strip tag svg lewat
        // Str::sanitizeHtml() saat TextColumn::html() dirender (lihat
        // ExamTypeCode::emoji() untuk penjelasan yang sama).
        $waktu = '<div class="jadwal-waktu-col jadwal-box">'
            .'<span class="jadwal-group-label">Waktu &amp; Lokasi:</span>'
            .'<div class="jadwal-waktu-list">'
            .'<span class="jadwal-waktu-item"><span class="jadwal-waktu-icon">📅</span>'.e($tanggal).'</span>'
            .'<span class="jadwal-waktu-item"><span class="jadwal-waktu-icon">🕐</span>'.e($jam).'</span>'
            .'<span class="jadwal-waktu-item"><span class="jadwal-waktu-icon">📍</span>'.e($ruang).'</span>'
            .'</div>'
            .'</div>';

        return '<div class="jadwal-body-row">'.$waktu.$this->buildPengujiHierarchyHtml($record).'</div>';
    }

    /**
     * Daftar penguji urut tetap 1–5 (examiner1–3, lalu guide1–2) dipecah
     * jadi 3 baris terpisah: Ketua sendirian (chief_id, di slot manapun,
     * badge biru + 👑 — cukup emoji mahkota + nama, tanpa teks "Ketua:"
     * karena perannya sudah terwakili ikonnya), Anggota penguji lain
     * mengalir bebas (nama polos tanpa label), lalu kedua Pembimbing
     * selalu di baris terpisah miliknya ("Nama (P1)"/"Nama (P2)"). Kalau
     * ketua kebetulan salah satu pembimbing, dia hanya tampil di baris
     * Ketua (tidak dobel).
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

        $chief = null;

        foreach ($slots as $slot) {
            if (blank($slot['id'])) {
                continue;
            }

            if ($record->chief_id && (int) $slot['id'] === (int) $record->chief_id) {
                $chief = $slot;

                break;
            }
        }

        $anggota = '';
        $pembimbing = '';

        foreach ($slots as $slot) {
            if (blank($slot['id']) || $slot === $chief) {
                continue;
            }

            $name = $slot['name'] ?? '(?)';

            if ($slot['kind'] === 'pembimbing') {
                $kode = str_replace('Pembimbing ', 'P', $slot['label']); // "P1" / "P2"
                $pembimbing .= '<span class="jadwal-badge jadwal-badge-pembimbing">'.e($name).' ('.e($kode).')</span>';
            } else {
                $anggota .= '<span class="jadwal-badge jadwal-badge-penguji">'.e($name).'</span>';
            }
        }

        $html = '<div class="jadwal-penguji-col jadwal-box">';
        $html .= '<span class="jadwal-group-label">Tim Penguji:</span>';
        $html .= '<div class="jadwal-penguji-rows">';

        if ($chief) {
            $html .= '<div class="jadwal-ketua-row"><span class="jadwal-badge jadwal-badge-ketua">&#128081; '.e($chief['name'] ?? '(?)').'</span></div>';
        }

        if ($anggota !== '') {
            $html .= '<div class="jadwal-anggota-row">'.$anggota.'</div>';
        }

        if ($pembimbing !== '') {
            $html .= '<div class="jadwal-pembimbing-row">'.$pembimbing.'</div>';
        }

        if (! $chief && $anggota === '' && $pembimbing === '') {
            $html .= '<span class="jadwal-judul-empty">—</span>';
        }

        $html .= '</div></div>';

        return $html;
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
     * Disalin dari WelcomeController::index() — satu baris rekap per
     * angkatan, dipakai kartu "Rekap Kelulusan & Ujian Skripsi" di
     * beranda.blade.php, tautan tiap angka mengarah ke RecapList.
     *
     * Aturan kategori (persis empat kelompok yang saling lepas & menutup
     * seluruh Total — lihat RecapList::buildQuery() untuk filter list-nya):
     * - Lulus: thesis_date terisi DI guide_examiners DAN ExamRegistration
     *   sidang (exam_type_id=3) sudah pass_exam=1. thesis_date terisi saja
     *   TIDAK cukup — tanggal sidang bisa sudah dituliskan di
     *   guide_examiners padahal hasilnya belum/tidak lulus (pass_exam masih
     *   null atau 0). Lihat lulusUserIds().
     * - Belum Lulus: Total - Lulus.
     * - Belum Sempro: proposal_date, seminar_date, thesis_date semua kosong.
     * - Akan Semhas: proposal_date terisi, seminar_date & thesis_date kosong.
     * - Akan Sidang: seminar_date terisi DAN belum termasuk Lulus (bukan
     *   whereNull('thesis_date') lagi — mahasiswa yang tanggal sidangnya
     *   sudah tertulis tapi belum pass_exam=1 tetap di sini, bukan pindah
     *   ke Lulus).
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

            $lulusIds = $this->lulusUserIds($angkatan);
            $lulus = $lulusIds->count();

            $belumLulus = $total - $lulus;

            $belumSemproIds = GuideExaminer::where('year_generation', $angkatan)
                ->whereNull('proposal_date')
                ->whereNull('seminar_date')
                ->whereNull('thesis_date')
                ->pluck('user_id');

            $akanSemhasIds = GuideExaminer::where('year_generation', $angkatan)
                ->whereNotNull('proposal_date')
                ->whereNull('seminar_date')
                ->whereNull('thesis_date')
                ->pluck('user_id');

            $akanSidangIds = GuideExaminer::where('year_generation', $angkatan)
                ->whereNotNull('seminar_date')
                ->pluck('user_id')
                ->diff($lulusIds)
                ->values();

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
     * user_id yang BENAR-BENAR lulus untuk $angkatan: thesis_date terisi di
     * guide_examiners DAN punya ExamRegistration sidang (exam_type_id=3)
     * dengan pass_exam=1. thesis_date terisi sendirian tidak cukup —lihat
     * catatan di rekap().
     *
     * @return Collection<int, int>
     */
    private function lulusUserIds(int|string $angkatan): Collection
    {
        $thesisDateIds = GuideExaminer::where('year_generation', $angkatan)
            ->whereNotNull('thesis_date')
            ->pluck('user_id');

        if ($thesisDateIds->isEmpty()) {
            return collect();
        }

        return ExamRegistration::whereIn('user_id', $thesisDateIds)
            ->where('exam_type_id', 3)
            ->where('pass_exam', 1)
            ->pluck('user_id')
            ->unique()
            ->values();
    }

    /**
     * Di antara $userIds, berapa yang punya ExamRegistration exam_type_id
     * $examTypeId dengan pass_exam belum 1 (0 atau null) — "sudah mendaftar
     * tapi belum lulus/dinilai".
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
            ->where(fn (Builder $query) => $query
                ->whereNull('pass_exam')
                ->orWhere('pass_exam', '!=', 1))
            ->distinct('user_id')
            ->count('user_id');
    }
}
