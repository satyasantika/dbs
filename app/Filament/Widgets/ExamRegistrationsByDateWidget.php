<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ExamRegistrationResource;
use App\Models\ExamRegistration;
use App\Services\Examination\SintesysExamRegistrationImporter;
use App\Services\Sintesys\SintesysException;
use App\Support\ExamFileLinkPaste;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ExamRegistrationsByDateWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.exam-registrations-by-date-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public ?string $examDate = null;

    public string $calendarMonth = '';

    public int $calendarYear;

    public int $calendarMonthNum;

    public function mount(): void
    {
        $this->examDate = now()->toDateString();
        $this->calendarMonth = now()->format('Y-m');
        $this->syncCalendarPartsFromMonth();
    }

    public function updatedExamDate(): void
    {
        if (filled($this->examDate)) {
            $this->calendarMonth = Carbon::parse($this->examDate)->format('Y-m');
            $this->syncCalendarPartsFromMonth();
        }

        $this->resetPage();
        $this->flushCachedTableRecords();
    }

    public function updatedCalendarYear(): void
    {
        $this->syncCalendarMonthFromParts();
    }

    public function updatedCalendarMonthNum(): void
    {
        $this->syncCalendarMonthFromParts();
    }

    public function selectExamDate(string $date): void
    {
        $this->examDate = $date;
        $this->calendarMonth = Carbon::parse($date)->format('Y-m');
        $this->syncCalendarPartsFromMonth();
        $this->updatedExamDate();
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth . '-01')
            ->subMonth()
            ->format('Y-m');
        $this->syncCalendarPartsFromMonth();
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth . '-01')
            ->addMonth()
            ->format('Y-m');
        $this->syncCalendarPartsFromMonth();
    }

    public function goToToday(): void
    {
        $this->selectExamDate(now()->toDateString());
    }

    /**
     * @return array<int, string>
     */
    public function getCalendarMonthOptionsProperty(): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = Carbon::create(null, $month, 1)
                ->locale(app()->getLocale())
                ->isoFormat('MMMM');
        }

        return $months;
    }

    /**
     * @return array<int, string>
     */
    public function getCalendarYearOptionsProperty(): array
    {
        [$minYear, $maxYear] = $this->getExamDateYearBounds();

        $years = [];

        for ($year = $maxYear; $year >= $minYear; $year--) {
            $years[$year] = (string) $year;
        }

        return $years;
    }

    /**
     * @return array<string, int>
     */
    public function getExamCountsByDateProperty(): array
    {
        $month = Carbon::parse($this->calendarMonth . '-01');

        return ExamRegistrationResource::getEloquentQuery()
            ->whereBetween('exam_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->get(['exam_date'])
            ->countBy(fn ($registration) => $registration->exam_date->toDateString())
            ->all();
    }

    /**
     * @return list<list<array{date: string, day: int, inMonth: bool, isToday: bool, isSelected: bool, count: int}>>
     */
    public function getCalendarWeeksProperty(): array
    {
        $month = Carbon::parse($this->calendarMonth . '-01');
        $counts = $this->examCountsByDate;

        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $weeks = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $week = [];

            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $date = $current->toDateString();

                $week[] = [
                    'date' => $date,
                    'day' => $current->day,
                    'inMonth' => $current->month === $month->month,
                    'isToday' => $current->isToday(),
                    'isSelected' => $date === $this->examDate,
                    'count' => $counts[$date] ?? 0,
                ];

                $current->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    public function getSelectedDateLabelProperty(): string
    {
        if (blank($this->examDate)) {
            return '—';
        }

        return Carbon::parse($this->examDate)
            ->locale(app()->getLocale())
            ->isoFormat('dddd, D MMMM YYYY');
    }

    public function table(Table $table): Table
    {
        // Card grid, bukan configureListTable() (tabel biasa yang dipakai
        // ExamRegistrationResource::table() lama & masih dipakai apa adanya
        // di tempat lain) — reuse getCardColumns()/getTableActions() milik
        // resource itu supaya tidak duplikasi definisi kolom. Dibungkus
        // data-grid-fit="rows" data-grid-fit-rows="2" di view widget ini
        // (bukan mengikuti tinggi layar seperti grid lain).
        return $table
            ->query(fn (): Builder => $this->selectedDateRegistrationsQuery())
            ->contentGrid([
                'default' => 1,
            ])
            ->columns(ExamRegistrationResource::getCardColumns())
            ->actions(ExamRegistrationResource::getTableActions())
            ->bulkActions([])
            ->defaultSort('exam_time', 'asc')
            ->emptyStateHeading('Tidak ada ujian pada tanggal ini')
            ->emptyStateDescription('Pilih tanggal ujian lain pada kalender untuk melihat jadwal.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->searchPlaceholder('Cari mahasiswa, NIM, atau penguji...')
            ->headerActions([
                Tables\Actions\Action::make('syncSintesys')
                    ->label('Sinkronisasi Sintesys')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(fn () => $this->syncSintesysForSelectedDate()),
                $this->pasteExamFileLinksAction(),
            ])
            ->paginated([10, 25, 50]);
    }

    /**
     * Isi Link File Ujian (exam_file) banyak mahasiswa sekaligus untuk
     * tanggal yang sedang dipilih di kalender — tidak perlu pilih tanggal
     * lagi di modal. Paste "NPM <tab> link" dari Excel; preview di bawah
     * textarea ikut diperbarui setiap kali isinya berubah.
     */
    protected function pasteExamFileLinksAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('pasteExamFileLinks')
            ->label('Link Drive File Ujian')
            ->icon('heroicon-o-link')
            ->color('info')
            ->modalHeading(fn (): string => 'Link Drive File Ujian — '.$this->selectedDateLabel)
            ->modalDescription('Copy dua kolom dari Excel (NPM, lalu link drive) dan paste di bawah. Hanya mahasiswa yang ujian pada tanggal ini yang diperbarui.')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Simpan Link')
            ->form([
                Forms\Components\Textarea::make('paste')
                    ->label('Data NPM & Link')
                    ->placeholder("222151146\thttps://drive.google.com/...\n222151147\thttps://drive.google.com/...")
                    ->rows(6)
                    ->required()
                    ->live(debounce: 400),
                Forms\Components\Placeholder::make('preview')
                    ->label('Preview')
                    ->content(fn (Get $get): HtmlString => new HtmlString(view(
                        'filament.widgets.exam-file-link-paste-preview',
                        ['rows' => ExamFileLinkPaste::preview($get('paste'), $this->examFileLinkTargetsQuery())],
                    )->render())),
            ])
            ->action(function (array $data): void {
                $result = ExamFileLinkPaste::apply($data['paste'] ?? '', $this->examFileLinkTargetsQuery());

                $this->flushCachedTableRecords();

                Notification::make()
                    ->success()
                    ->title('Link file ujian disimpan')
                    ->body("Disimpan: {$result['saved']} · Dilewati: {$result['skipped']}")
                    ->send();
            });
    }

    protected function selectedDateRegistrationsQuery(): Builder
    {
        return ExamRegistrationResource::getEloquentQuery()
            ->whereDate('exam_date', $this->examDate ?: now()->toDateString());
    }

    /**
     * Tanpa eager load kartu (getEloquentQuery()) — preview dihitung ulang
     * setiap ketikan, cukup kolom yang dipakai ExamFileLinkPaste.
     */
    protected function examFileLinkTargetsQuery(): Builder
    {
        return ExamRegistration::query()
            ->select(['id', 'user_id', 'exam_file'])
            ->whereDate('exam_date', $this->examDate ?: now()->toDateString());
    }

    public function syncSintesysForSelectedDate(): void
    {
        $date = filled($this->examDate) ? $this->examDate : now()->toDateString();

        try {
            $result = app(SintesysExamRegistrationImporter::class)->persist($date, $date);
        } catch (SintesysException $e) {
            Notification::make()
                ->danger()
                ->title('Sinkronisasi Sintesys gagal')
                ->body($e->getMessage())
                ->send();

            return;
        }

        $this->flushCachedTableRecords();
        $this->resetPage();

        $body = "Baru: {$result['created']} · Diperbarui: {$result['updated']} · Dilewati: {$result['skipped']}";

        if ($result['errors'] !== []) {
            $body .= "\n".implode("\n", array_slice($result['errors'], 0, 5));
        }

        Notification::make()
            ->success()
            ->title('Sinkronisasi Sintesys selesai')
            ->body($body)
            ->send();
    }

    protected function syncCalendarPartsFromMonth(): void
    {
        $month = Carbon::parse($this->calendarMonth . '-01');

        $this->calendarYear = $month->year;
        $this->calendarMonthNum = $month->month;
    }

    protected function syncCalendarMonthFromParts(): void
    {
        [$minYear, $maxYear] = $this->getExamDateYearBounds();

        $this->calendarYear = max($minYear, min($maxYear, $this->calendarYear));
        $this->calendarMonthNum = max(1, min(12, $this->calendarMonthNum));

        $this->calendarMonth = sprintf('%04d-%02d', $this->calendarYear, $this->calendarMonthNum);
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function getExamDateYearBounds(): array
    {
        $query = ExamRegistrationResource::getEloquentQuery();

        $minDate = $query->min('exam_date');
        $maxDate = $query->max('exam_date');

        $minYear = $minDate
            ? Carbon::parse($minDate)->year
            : now()->year - 5;

        $maxYear = max(
            now()->year + 1,
            $maxDate ? Carbon::parse($maxDate)->year : now()->year
        );

        return [$minYear, $maxYear];
    }
}
