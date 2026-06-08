<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SetScoringToExaminerResource\Pages;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SetScoringToExaminerResource extends Resource
{
    protected static ?string $model = ExamRegistration::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationLabel = 'Set Penguji';

    protected static ?string $modelLabel = 'Set penguji';

    protected static ?string $pluralModelLabel = 'Set penguji';

    protected static ?string $slug = 'set-scoring-to-examiner-yet';

    protected static ?int $navigationSort = 2;

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
        $assignedRegistrationIds = ExamScore::query()
            ->select('exam_registration_id')
            ->groupBy('exam_registration_id')
            ->pluck('exam_registration_id');

        return parent::getEloquentQuery()
            ->whereNotIn('id', $assignedRegistrationIds)
            ->with([
                'student:id,name',
                'examtype:id,name',
                'examiner1:id,name',
                'examiner2:id,name',
                'examiner3:id,name',
                'guide1:id,name',
                'guide2:id,name',
            ])
            ->orderBy('exam_date')
            ->orderBy('exam_time');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Peserta Ujian')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('examtype.name')
                    ->label('Ujian')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_schedule')
                    ->label('Waktu')
                    ->getStateUsing(function (ExamRegistration $record): string {
                        $date = $record->exam_date?->translatedFormat('d M Y') ?? '—';
                        $time = $record->exam_time
                            ? \Carbon\Carbon::parse($record->exam_time)->format('H:i')
                            : '—';

                        return "{$date} {$time}";
                    }),
                Tables\Columns\TextColumn::make('penguji')
                    ->label('Penguji')
                    ->getStateUsing(fn (ExamRegistration $record): string => ExamRegistrationResource::buildExaminerHtml($record))
                    ->html()
                    ->wrap()
                    ->sortable(false)
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                foreach (['examiner1', 'examiner2', 'examiner3', 'guide1', 'guide2'] as $rel) {
                                    $q->orWhereHas($rel, fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                                }
                            });
                        }
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->label('Jenis Ujian')
                    ->relationship('examtype', 'name'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->iconButton()
                    ->url(fn (ExamRegistration $record): string => ExamRegistrationResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('set')
                        ->label('Set ke penguji')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Set jadwal ke penguji')
                        ->modalDescription(fn (Collection $records): string => 'Yakin akan set '
                            . $records->count()
                            . ' ujian? Data para penguji akan ditambahkan ke sistem penilaian.')
                        ->action(function (Collection $records): void {
                            $records->each(fn (ExamRegistration $record) => static::assignExaminerScores($record));

                            Notification::make()
                                ->success()
                                ->title('Data para penguji telah ditambahkan')
                                ->body($records->count() . ' ujian berhasil diset ke penguji.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Tidak ada jadwal ujian yang belum diset ke penguji')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->defaultSort('exam_date', 'asc');
    }

    public static function assignExaminerScores(ExamRegistration $record): void
    {
        foreach ([
            ['user_id' => $record->examiner1_id, 'examiner_order' => 1],
            ['user_id' => $record->examiner2_id, 'examiner_order' => 2],
            ['user_id' => $record->examiner3_id, 'examiner_order' => 3],
            ['user_id' => $record->guide1_id, 'examiner_order' => 4],
            ['user_id' => $record->guide2_id, 'examiner_order' => 5],
        ] as $slot) {
            ExamScore::create([
                'exam_registration_id' => $record->id,
                'user_id' => $slot['user_id'],
                'examiner_order' => $slot['examiner_order'],
            ]);
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSetScoringToExaminers::route('/'),
        ];
    }
}
