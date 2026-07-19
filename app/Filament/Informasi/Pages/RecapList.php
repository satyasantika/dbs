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
 * Aturan kategori per $context (harus sama persis dengan
 * Beranda::rekap() supaya jumlahnya cocok):
 * - Lulus: thesis_date terisi DI guide_examiners DAN ExamRegistration
 *   sidang (exam_type_id=3) sudah pass_exam=1 — thesis_date terisi saja
 *   tidak cukup (tanggal sidang bisa sudah tertulis padahal hasilnya
 *   belum/tidak lulus). Lihat Beranda::lulusUserIds().
 * - Belum Lulus: bukan Lulus (lihat definisi Lulus di atas).
 * - Belum Sempro: proposal_date, seminar_date, thesis_date semua kosong.
 * - Akan Semhas: proposal_date terisi, seminar_date & thesis_date kosong.
 * - Akan Sidang: seminar_date terisi DAN bukan Lulus (bukan
 *   whereNull('thesis_date') lagi, per alasan yang sama seperti Lulus).
 * Kolom "Status" (badge "Sudah daftar, menunggu hasil") menandai mahasiswa
 * yang sudah terdaftar di exam_registrations untuk jenis ujian berikutnya
 * tapi pass_exam belum 1 — hanya tampil di 3 context "akan/belum" di atas,
 * jumlahnya harus cocok dengan angka "* reg" di kartu rekap Beranda.
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
                        ->getStateUsing(fn (GuideExaminer $record): ?string => ($nextExamTypeId !== null && $this->isRegisteredNotPassed($record, $nextExamTypeId))
                            ? 'Sudah daftar, menunggu hasil'
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
     * pass_exam belum 1 (0 atau null) — "sudah mendaftar tapi belum lulus/
     * dinilai". Makan dari relasi examRegistrations yang di-eager-load di
     * buildQuery(), bukan query baru per baris.
     */
    private function isRegisteredNotPassed(GuideExaminer $record, int $examTypeId): bool
    {
        return $record->examRegistrations
            ->where('exam_type_id', $examTypeId)
            ->contains(fn (ExamRegistration $registration): bool => ! $registration->pass_exam);
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

    private function buildQuery(): Builder
    {
        $generation = $this->generation;

        $base = fn (): Builder => GuideExaminer::query()
            ->with(['student', 'guide1', 'guide2', 'examRegistrations'])
            ->where('year_generation', $generation);

        $lulus = fn (Builder $query): Builder => $query
            ->whereNotNull('thesis_date')
            ->whereHas('examRegistrations', fn (Builder $q) => $q
                ->where('exam_type_id', 3)
                ->where('pass_exam', 1));

        $belumLulus = fn (Builder $query): Builder => $query
            ->where(fn (Builder $q) => $q
                ->whereNull('thesis_date')
                ->orWhereDoesntHave('examRegistrations', fn (Builder $q2) => $q2
                    ->where('exam_type_id', 3)
                    ->where('pass_exam', 1)));

        return match ($this->context) {
            'Mahasiswa Lulus' => $lulus($base()),

            'Mahasiswa Belum Lulus' => $belumLulus($base()),

            'Mahasiswa Belum Sempro' => $base()
                ->whereNull('proposal_date')
                ->whereNull('seminar_date')
                ->whereNull('thesis_date'),

            'Mahasiswa Akan Semhas' => $base()
                ->whereNotNull('proposal_date')
                ->whereNull('seminar_date')
                ->whereNull('thesis_date'),

            'Mahasiswa Akan Sidang' => $belumLulus($base()->whereNotNull('seminar_date')),

            default => $base(),
        };
    }
}
