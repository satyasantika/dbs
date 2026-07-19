<?php

namespace App\Filament\Informasi\Pages;

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
                // cuma ->contentGrid() saja. ->prefix() dipakai di beberapa
                // kolom karena mode card tidak menampilkan header kolom
                // seperti tabel. Urutan field: jenis ujian, nama mahasiswa,
                // NIM, judul, tanggal, jam, ruang, para penguji.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('examtype.name')
                            ->label('Jenis Ujian')
                            ->badge()
                            ->grow(false),
                        Tables\Columns\TextColumn::make('student.name')
                            ->label('Mahasiswa')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('student.username')
                            ->label('NIM')
                            ->badge()
                            ->color('gray')
                            ->grow(false),
                    ]),
                    Tables\Columns\TextColumn::make('title')
                        ->label('Judul')
                        ->wrap()
                        ->placeholder('—')
                        ->color('gray'),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('exam_date')
                            ->label('Tanggal')
                            ->date('d M Y')
                            ->prefix('Tanggal: '),
                        Tables\Columns\TextColumn::make('exam_time')
                            ->label('Jam')
                            ->time('H:i')
                            ->prefix('Jam: '),
                        Tables\Columns\TextColumn::make('room')
                            ->label('Ruang Ujian')
                            ->placeholder('—')
                            ->prefix('Ruang: '),
                    ]),
                    Tables\Columns\TextColumn::make('para_penguji')
                        ->label('Para Penguji')
                        ->getStateUsing(fn (ExamRegistration $record): string => $this->buildPengujiList($record))
                        ->html(),
                ])->space(2),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada jadwal ujian mendatang')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    /**
     * Daftar penguji urut tetap 1–5 (examiner1–3, lalu guide1–2) — ketua
     * (chief_id, di slot manapun) & pembimbing (slot 4–5) ditandai warna
     * beda dari penguji biasa, per permintaan eksplisit di halaman publik
     * ini (beda dari ExamRegistrationResource::buildExaminerHtml() yang
     * cuma menandai ketua).
     */
    private function buildPengujiList(ExamRegistration $record): string
    {
        $slots = [
            ['no' => 1, 'label' => 'Penguji 1', 'id' => $record->examiner1_id, 'name' => $record->examiner1?->name, 'kind' => 'penguji'],
            ['no' => 2, 'label' => 'Penguji 2', 'id' => $record->examiner2_id, 'name' => $record->examiner2?->name, 'kind' => 'penguji'],
            ['no' => 3, 'label' => 'Penguji 3', 'id' => $record->examiner3_id, 'name' => $record->examiner3?->name, 'kind' => 'penguji'],
            ['no' => 4, 'label' => 'Pembimbing 1', 'id' => $record->guide1_id, 'name' => $record->guide1?->name, 'kind' => 'pembimbing'],
            ['no' => 5, 'label' => 'Pembimbing 2', 'id' => $record->guide2_id, 'name' => $record->guide2?->name, 'kind' => 'pembimbing'],
        ];

        $html = '<div class="beranda-penguji-list">';

        foreach ($slots as $slot) {
            if (blank($slot['id'])) {
                continue;
            }

            $isChief = $record->chief_id && (int) $slot['id'] === (int) $record->chief_id;
            $cssClass = $isChief ? 'beranda-penguji-ketua' : ($slot['kind'] === 'pembimbing' ? 'beranda-penguji-pembimbing' : 'beranda-penguji-biasa');
            $label = $slot['label'].($isChief ? ' &middot; Ketua' : '');

            $html .= '<span class="beranda-penguji-badge '.$cssClass.'">'.e($label).': '.e($slot['name'] ?? '(?)').'</span>';
        }

        $html .= '</div>';

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
     * - Lulus: thesis_date terisi, walau proposal_date/seminar_date kosong.
     * - Belum Lulus: Total - Lulus.
     * - Belum Sempro: proposal_date, seminar_date, thesis_date semua kosong.
     * - Akan Semhas: proposal_date terisi, seminar_date & thesis_date kosong.
     * - Akan Sidang: seminar_date terisi, thesis_date kosong.
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

            $lulus = GuideExaminer::where('year_generation', $angkatan)
                ->whereNotNull('thesis_date')
                ->count();

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
                ->whereNull('thesis_date')
                ->pluck('user_id');

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
