<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ExamRegistrationResource;
use Carbon\Carbon;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

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
        return ExamRegistrationResource::configureListTable(
            $table->query(fn (): Builder => ExamRegistrationResource::getEloquentQuery()
                ->whereDate('exam_date', $this->examDate ?: now()->toDateString()))
        )
            ->defaultSort('exam_time', 'asc')
            ->emptyStateHeading('Tidak ada ujian pada tanggal ini')
            ->emptyStateDescription('Pilih tanggal ujian lain pada kalender untuk melihat jadwal.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->paginated([10, 25, 50]);
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
