<?php

namespace App\Filament\Widgets;

use App\Models\ExamScore;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UnscoredExamsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Dosen Belum Menilai';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ExamScore::query()
                    ->with(['lecture', 'registration.student', 'registration.examtype'])
                    ->whereNull('pass_approved')
            )
            ->columns([
                Tables\Columns\TextColumn::make('lecture.name')
                    ->label('Penguji')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration.student.name')
                    ->label('Peserta Ujian')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration.examtype.name')
                    ->label('Jenis Ujian'),
                Tables\Columns\TextColumn::make('registration.exam_date')
                    ->label('Tanggal Ujian')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')
                    ->label('WA')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(fn (ExamScore $record): string =>
                        'https://api.whatsapp.com/send/?phone=62'
                        . ($record->lecture?->phone ?? '')
                        . '&text=Yth.%20Penguji%20'
                        . rawurlencode($record->registration?->student?->name ?? '-')
                        . ',%0A%0AMohon%20segera%20memberikan%20penilaian%20'
                        . rawurlencode($record->registration?->examtype?->name ?? '-')
                        . '%20pada%20'
                        . rawurlencode($record->registration?->exam_date?->isoFormat('dddd, D MMMM Y') ?? '-')
                        . '%0A%0Asilakan%20akses:%0A%0A'
                        . rawurlencode(url('/examination/scoring/' . $record->id . '/edit'))
                    )
                    ->openUrlInNewTab()
                    ->iconButton(),
            ])
            ->emptyStateHeading('Semua penguji sudah memberikan penilaian')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->defaultSort('registration.exam_date', 'asc')
            ->paginated([10, 25, 50]);
    }
}
