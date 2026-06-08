<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ExamRegistrationResource;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExamRegistrationsByDateWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.exam-registrations-by-date-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public ?string $examDate = null;

    public function mount(): void
    {
        $this->examDate = now()->toDateString();
    }

    public function table(Table $table): Table
    {
        return ExamRegistrationResource::configureListTable(
            $table->query(
                ExamRegistrationResource::getEloquentQuery()
                    ->whereDate('exam_date', $this->examDate ?? now()->toDateString())
            )
        )
            ->defaultSort('exam_time', 'asc')
            ->emptyStateHeading('Tidak ada ujian pada tanggal ini')
            ->emptyStateDescription('Pilih tanggal ujian lain untuk melihat jadwal.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->paginated([10, 25, 50]);
    }
}
