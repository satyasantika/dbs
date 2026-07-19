<?php

namespace App\Filament\Informasi\Pages;

use App\Models\ExamRegistration;
use App\Models\GuideExaminer;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Versi Filament (publik, tanpa login) dari
 * App\Http\Controllers\Information\GuideInformationController::recap() +
 * App\DataTables\InformationPassRecapDataTable — rute lama di
 * information/recap-list/{generation}/{context} TETAP ada dan tidak
 * disentuh (dipakai tests/Feature/DatatableSearchSmokeTest.php), halaman
 * ini hanya alternatif tampilan card grid dengan URL baru di panel
 * 'informasi'.
 *
 * PENTING: proposal_date/seminar_date/thesis_date di guide_examiners
 * di-stample begitu ExamRegistration didaftarkan (lihat
 * ExamRegistrationController::store()), SEBELUM ujian/nilai keluar — jadi
 * "tanggal terisi" TIDAK sama dengan "sudah lulus tahap itu" di KETIGA
 * tahap. Lihat penjelasan lengkap & contoh di docblock Beranda::rekap().
 *
 * Aturan kategori per $context (harus sama persis dengan Beranda::rekap()
 * supaya jumlahnya cocok — lihat applyTrulyPassed()):
 * - Lulus: trulyPassed('thesis_date', 3).
 * - Belum Lulus: NOT trulyPassed('thesis_date', 3).
 * - Belum Sempro: NOT trulyPassed('proposal_date', 1).
 * - Akan Semhas: trulyPassed('proposal_date', 1) AND NOT trulyPassed('seminar_date', 2).
 * - Akan Sidang: trulyPassed('seminar_date', 2) AND NOT trulyPassed('thesis_date', 3).
 * "trulyPassed" = tanggal terisi DAN TIDAK punya ExamRegistration
 * exam_type=N yang masih PENDING (pass_exam IS NULL) — exclusion-based,
 * bukan positive-requirement, supaya ExamRegistration yang terhapus/
 * tanggal yang diisi manual tidak salah menggugurkan status. TIDAK ada
 * pengecualian retake: kalau masih ada satu saja ExamRegistration
 * pass_exam=NULL untuk exam_type ini, user itu TETAP dianggap belum lulus
 * tahap ini, walau ada baris pass_exam=1 lain untuk exam_type yang sama —
 * rumus ini sudah dikonfirmasi eksplisit, lihat catatan lengkap di
 * Beranda::stageDisqualifiedIds(). pass_exam=0 SENGAJA tidak dipakai sama
 * sekali di seluruh rekap ini — cuma pass_exam=1 (lulus) & pass_exam=NULL
 * (pending) yang relevan.
 * Kolom "Status" (badge "Sudah daftar, menunggu ujian") menandai mahasiswa
 * yang sudah terdaftar di exam_registrations untuk jenis ujian berikutnya
 * dengan exam_date MASIH AKAN DATANG (belum diujiankan) — kriteria
 * tanggal, BUKAN pass_exam (lihat isRegisteredUpcoming()) — hanya tampil
 * di 3 context "akan/belum" di atas, jumlahnya harus cocok dengan angka
 * "* reg" di kartu rekap Beranda (Beranda::countUpcomingRegistrations()).
 * Tanggal SemPro/SemHas/Sidang HANYA ditampilkan kalau ExamRegistration
 * terkait sudah pass_exam=1 ATAU tanggal ujiannya sudah lewat — supaya
 * tidak menampilkan tanggal ujian mendatang seolah sudah pasti/selesai.
 */
class RecapList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $slug = 'recap-list/{generation}/{context}';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.informasi.pages.recap-list';

    public string $generation;

    public string $context;

    public function mount(string $generation, string $context): void
    {
        $this->generation = $generation;
        $this->context = urldecode($context);
    }

    public function getTitle(): string|Htmlable
    {
        return "Rekap {$this->context} — Angkatan {$this->generation}";
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'informasi', $tenant);
    }

    public function table(Table $table): Table
    {
        $nextExamTypeId = $this->nextExamTypeIdForContext();

        return $table
            ->query(fn (): Builder => $this->buildQuery())
            ->contentGrid([
                'default' => 1,
            ])
            ->columns([
                // Dibungkus Layout\Stack — ini yang membuat Filament benar-benar
                // merender tiap baris sebagai card (hasColumnsLayout()), bukan
                // cuma ->contentGrid() saja.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('student.name')
                            ->label('Mahasiswa')
                            ->searchable()
                            ->sortable()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('student.username')
                            ->label('NIM')
                            ->searchable()
                            ->badge()
                            ->color('gray')
                            ->grow(false),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('guide1.name')
                            ->label('P1')
                            ->placeholder('—'),
                        Tables\Columns\TextColumn::make('guide2.name')
                            ->label('P2')
                            ->placeholder('—'),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        // Hanya tanggal yyyy-mm-dd — tanpa jam, tanpa format lain
                        // — dan hanya tampil kalau sudah pass_exam=1 atau
                        // tanggal ujiannya sudah lewat (lihat shouldShowMilestoneDate()).
                        Tables\Columns\TextColumn::make('proposal_date')
                            ->label('SemPro')
                            ->getStateUsing(fn (GuideExaminer $record): ?string => $this->shouldShowMilestoneDate($record, 1)
                                ? $record->proposal_date?->format('Y-m-d')
                                : null)
                            ->placeholder('—'),
                        Tables\Columns\TextColumn::make('seminar_date')
                            ->label('SemHas')
                            ->getStateUsing(fn (GuideExaminer $record): ?string => $this->shouldShowMilestoneDate($record, 2)
                                ? $record->seminar_date?->format('Y-m-d')
                                : null)
                            ->placeholder('—'),
                        Tables\Columns\TextColumn::make('thesis_date')
                            ->label('Sidang')
                            ->getStateUsing(fn (GuideExaminer $record): ?string => $this->shouldShowMilestoneDate($record, 3)
                                ? $record->thesis_date?->format('Y-m-d')
                                : null)
                            ->placeholder('—'),
                    ]),
                    Tables\Columns\TextColumn::make('sudah_daftar')
                        ->label('Status')
                        ->getStateUsing(fn (GuideExaminer $record): ?string => ($nextExamTypeId !== null && $this->isRegisteredUpcoming($record, $nextExamTypeId))
                            ? 'Sudah daftar, menunggu ujian'
                            : null)
                        ->badge()
                        ->color('warning')
                        ->visible($nextExamTypeId !== null),
                ])->space(2),
            ])
            ->defaultSort('student.name')
            ->emptyStateHeading('Tidak ada data untuk angkatan/kategori ini')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    /**
     * exam_type_id ujian BERIKUTNYA yang relevan untuk $context ini (dipakai
     * kolom "Status" — badge "sudah daftar" hanya masuk akal untuk 3 context
     * "menuju" ini). Null untuk context lain (Total/Lulus/Belum Lulus).
     */
    private function nextExamTypeIdForContext(): ?int
    {
        return match ($this->context) {
            'Mahasiswa Belum Sempro' => 1,
            'Mahasiswa Akan Semhas' => 2,
            'Mahasiswa Akan Sidang' => 3,
            default => null,
        };
    }

    /**
     * $record sudah punya ExamRegistration exam_type_id=$examTypeId dengan
     * exam_date MASIH AKAN DATANG (hari ini atau setelahnya) — "sudah
     * mendaftar tapi belum diujiankan". Murni kriteria tanggal, BUKAN
     * pass_exam — harus sama persis dengan
     * Beranda::countUpcomingRegistrations() supaya jumlahnya cocok dengan
     * angka "* reg" di kartu rekap Beranda. Makan dari relasi
     * examRegistrations yang di-eager-load di buildQuery(), bukan query
     * baru per baris.
     */
    private function isRegisteredUpcoming(GuideExaminer $record, int $examTypeId): bool
    {
        $today = now()->toDateString();

        return $record->examRegistrations
            ->where('exam_type_id', $examTypeId)
            ->contains(fn (ExamRegistration $registration): bool => $registration->exam_date?->toDateString() >= $today);
    }

    /**
     * Tanggal milestone (SemPro/SemHas/Sidang) hanya ditampilkan kalau ada
     * ExamRegistration untuk exam_type_id yang sama dengan pass_exam=1 ATAU
     * tanggal ujiannya sudah lewat — supaya tidak menampilkan tanggal ujian
     * mendatang seolah sudah pasti/selesai.
     */
    private function shouldShowMilestoneDate(GuideExaminer $record, int $examTypeId): bool
    {
        $today = now()->toDateString();

        return $record->examRegistrations
            ->where('exam_type_id', $examTypeId)
            ->contains(fn (ExamRegistration $registration): bool => $registration->pass_exam
                || ($registration->exam_date?->toDateString() < $today));
    }

    /**
     * Terapkan kondisi "benar-benar lulus tahap $examTypeId" ke $query —
     * versi Builder dari Beranda::trulyPassedIds()/stageDisqualifiedIds(),
     * pakai De Morgan supaya bisa dinegasikan ($negate) tanpa query
     * terpisah. trulyPassedN = $dateColumn tidak null AND TIDAK punya
     * ExamRegistration exam_type=N yang masih PENDING (pass_exam IS NULL).
     *
     * Rumus persis (jangan diubah tanpa alasan kuat — sudah dikonfirmasi
     * eksplisit, harus sama persis dengan Beranda::stageDisqualifiedIds()):
     * TIDAK ada pengecualian retake — kalau masih ada satu saja
     * ExamRegistration pass_exam=NULL untuk exam_type_id ini, user itu
     * TETAP dianggap belum lulus tahap ini, WALAUPUN ada baris pass_exam=1
     * lain untuk exam_type yang sama. pass_exam=0 sengaja tidak dipakai
     * sama sekali di query ini.
     */
    private function applyTrulyPassed(Builder $query, string $dateColumn, int $examTypeId, bool $negate = false): Builder
    {
        if (! $negate) {
            return $query
                ->whereNotNull($dateColumn)
                ->whereDoesntHave('examRegistrations', fn (Builder $q) => $q->where('exam_type_id', $examTypeId)->whereNull('pass_exam'));
        }

        // NOT(dateColumn terisi AND tak punya reg pending) = dateColumn kosong OR punya reg pending
        return $query->where(fn (Builder $q) => $q
            ->whereNull($dateColumn)
            ->orWhereHas('examRegistrations', fn (Builder $q2) => $q2->where('exam_type_id', $examTypeId)->whereNull('pass_exam')));
    }

    private function buildQuery(): Builder
    {
        $generation = $this->generation;

        $base = fn (): Builder => GuideExaminer::query()
            ->with(['student', 'guide1', 'guide2', 'examRegistrations'])
            ->where('year_generation', $generation);

        return match ($this->context) {
            'Mahasiswa Lulus' => $this->applyTrulyPassed($base(), 'thesis_date', 3),

            'Mahasiswa Belum Lulus' => $this->applyTrulyPassed($base(), 'thesis_date', 3, negate: true),

            'Mahasiswa Belum Sempro' => $this->applyTrulyPassed($base(), 'proposal_date', 1, negate: true),

            'Mahasiswa Akan Semhas' => $this->applyTrulyPassed($base(), 'proposal_date', 1)
                ->where(fn (Builder $q) => $this->applyTrulyPassed($q, 'seminar_date', 2, negate: true)),

            'Mahasiswa Akan Sidang' => $this->applyTrulyPassed($base(), 'seminar_date', 2)
                ->where(fn (Builder $q) => $this->applyTrulyPassed($q, 'thesis_date', 3, negate: true)),

            default => $base(),
        };
    }
}
