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
 * 'informasi'. Logika filter per $context sengaja disalin persis dari
 * InformationPassRecapDataTable::query() supaya hasilnya identik.
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
                        // Hanya tanggal yyyy-mm-dd — tanpa jam, tanpa format lain.
                        Tables\Columns\TextColumn::make('proposal_date')
                            ->label('SemPro')
                            ->date('Y-m-d')
                            ->placeholder('—')
                            ->sortable(),
                        Tables\Columns\TextColumn::make('seminar_date')
                            ->label('SemHas')
                            ->date('Y-m-d')
                            ->placeholder('—')
                            ->sortable(),
                        Tables\Columns\TextColumn::make('thesis_date')
                            ->label('Sidang')
                            ->date('Y-m-d')
                            ->placeholder('—')
                            ->sortable(),
                    ]),
                ])->space(2),
            ])
            ->defaultSort('student.name')
            ->emptyStateHeading('Tidak ada data untuk angkatan/kategori ini')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    private function buildQuery(): Builder
    {
        $generation = $this->generation;

        // Meniru join asli (InformationPassRecapDataTable::query()): user_id
        // ExamRegistration exam_type_id=$examTypeId & pass_exam=0, dibatasi ke
        // mahasiswa guide_examiners angkatan ini.
        $registeredNotPassed = fn (int $examTypeId) => ExamRegistration::query()
            ->where('exam_type_id', $examTypeId)
            ->where('pass_exam', 0)
            ->whereIn('user_id', GuideExaminer::query()->where('year_generation', $generation)->pluck('user_id'))
            ->pluck('user_id');

        $base = fn (): Builder => GuideExaminer::query()
            ->with(['student', 'guide1', 'guide2'])
            ->where('year_generation', $generation);

        // InformationPassRecapDataTable::query() aslinya pakai SQL UNION untuk
        // menggabungkan dua kondisi per context. UNION dihindari di sini dan
        // diganti kondisi OR dalam satu query — union() tidak bisa dipakai
        // berdampingan dengan ->defaultSort()/->sortable() pada kolom relasi
        // (student.name): MySQL menolak ORDER BY yang mereferensikan tabel
        // hasil join ("Table ... cannot be used in ORDER BY") begitu query
        // dasarnya berupa UNION. Hasil logisnya tetap sama.
        return match ($this->context) {
            'Mahasiswa Lulus' => $base()
                ->whereNotNull('thesis_date')
                ->whereNotIn('user_id', $registeredNotPassed(3)),

            'Mahasiswa Belum Lulus' => $base()
                ->where(fn (Builder $query) => $query
                    ->whereNull('thesis_date')
                    ->orWhereIn('user_id', $registeredNotPassed(3))),

            'Mahasiswa Belum Sempro' => $base()
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $q) => $q
                        ->whereNull('proposal_date')
                        ->whereNull('seminar_date')
                        ->whereNull('thesis_date'))
                    ->orWhereIn('user_id', $registeredNotPassed(1))),

            'Mahasiswa Akan Semhas' => $base()
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $q) => $q
                        ->whereNotNull('proposal_date')
                        ->whereNull('seminar_date')
                        ->whereNull('thesis_date')
                        ->whereNotIn('user_id', $registeredNotPassed(1)))
                    ->orWhereIn('user_id', $registeredNotPassed(2))),

            'Mahasiswa Akan Sidang' => $base()
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $q) => $q
                        ->whereNotNull('proposal_date')
                        ->whereNotNull('seminar_date')
                        ->whereNull('thesis_date')
                        ->whereNotIn('user_id', $registeredNotPassed(2)))
                    ->orWhereIn('user_id', $registeredNotPassed(3))),

            default => $base(),
        };
    }
}
