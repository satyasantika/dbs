<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReadyExamResultsResource\Pages;
use App\Models\ExamRegistration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReadyExamResultsResource extends Resource
{
    protected static ?string $model = ExamRegistration::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationLabel = 'Siap Kirim Hasil';

    protected static ?string $modelLabel = 'Ujian siap kirim';

    protected static ?string $pluralModelLabel = 'Siap kirim hasil';

    protected static ?string $slug = 'exam-results-ready-to-send';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->readyToNotifyStudent()
            ->with([
                'examScores.lecture',
                'examiner1', 'examiner2', 'examiner3',
                'guide1', 'guide2',
                'student',
                'examtype',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('examtype.name')
                    ->label('Jenis ujian')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_order')
                    ->label('Ke')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_schedule')
                    ->label('Tanggal & waktu ujian')
                    ->getStateUsing(function (ExamRegistration $record): string {
                        $date = $record->exam_date?->translatedFormat('d M Y') ?? '—';
                        $time = $record->exam_time
                            ? \Carbon\Carbon::parse($record->exam_time)->format('H:i')
                            : '—';

                        return "{$date}, {$time}";
                    }),
                Tables\Columns\TextColumn::make('room')
                    ->label('Ruang')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        '1' => 'Ruang Ujian 1',
                        '2' => 'Ruang Ujian 2',
                        '3' => 'Ruang Ujian 3',
                        '4' => 'Ruang Ujian 4',
                        default => $state ?: '—',
                    }),
                Tables\Columns\TextColumn::make('pass_exam')
                    ->label('Lulus')
                    ->getStateUsing(fn (ExamRegistration $record): string => ExamRegistrationResource::buildPassSendHtml($record))
                    ->html()
                    ->sortable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->label('Jenis Ujian')
                    ->relationship('examtype', 'name'),
                Tables\Filters\Filter::make('exam_date')
                    ->label('Tanggal Ujian')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('exam_date', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('exam_date', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dari: '.$data['from']);
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Sampai: '.$data['until']);
                        }

                        return $indicators;
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\Action::make('view_scores')
                    ->label('Rincian Penilaian')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->iconButton()
                    ->modalHeading(function (ExamRegistration $record): string {
                        $record->loadMissing(['examtype', 'student']);
                        $type = $record->examtype?->name ?? 'Ujian';
                        $name = $record->student?->name ?? '';

                        return "Penilaian {$type} (ke-{$record->registration_order}) — {$name}";
                    })
                    ->modalContent(fn (ExamRegistration $record) => view('filament.modals.exam-scores-detail', ['recordId' => $record->id]))
                    ->modalWidth(\Filament\Support\Enums\MaxWidth::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\Action::make('kabari')
                    ->label('kabari')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->iconButton()
                    ->tooltip('Buka WhatsApp di tab baru dan tandai pesan terkirim (kirim ulang memperbarui waktu terkirim)')
                    ->action(fn (ExamRegistration $record, $livewire) => ExamRegistrationResource::kabariMahasiswaLewatWhatsapp($record, $livewire)),
            ])
            ->bulkActions([])
            ->defaultSort('exam_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReadyExamResults::route('/'),
        ];
    }
}
