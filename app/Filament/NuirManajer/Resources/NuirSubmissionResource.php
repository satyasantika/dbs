<?php

namespace App\Filament\NuirManajer\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource\RelationManagers;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NuirSubmissionResource extends Resource
{
    use AuthorizesNuirRolePanelAccess;

    protected static ?string $model = NuirSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationGroup = 'Manajemen NUIR';

    protected static ?string $modelLabel = 'Submission NUIR';

    protected static ?string $pluralModelLabel = 'Submission NUIR';

    protected static ?int $navigationSort = 1;

    protected static function nuirRoleAccessPermission(): string
    {
        return 'access dashboard manajer nuir';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Mahasiswa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('year_generation')->label('Angkatan')->sortable(),
                Tables\Columns\TextColumn::make('version')->label('Versi')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('assignment.validator.name')->label('Validator')->placeholder('Belum ditugaskan'),
                Tables\Columns\TextColumn::make('references_validated_count')
                    ->label('Referensi Divalidasi')
                    ->formatStateUsing(fn ($state, NuirSubmission $record): string => ($state ?? 0).'/'.($record->references_total_count ?? 0))
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_validation_status')
                    ->label('Progress Validasi')
                    ->badge()
                    ->state(fn (NuirSubmission $record): string => static::referenceValidationStatusFromCounts(
                        (int) ($record->references_validated_count ?? 0),
                        (int) ($record->references_total_count ?? 0),
                    ))
                    ->formatStateUsing(fn (string $state): string => NuirSubmission::referenceValidationStatusLabel($state))
                    ->color(fn (string $state): string => NuirSubmission::referenceValidationStatusColor($state)),
                Tables\Columns\TextColumn::make('updated_at')->label('Diperbarui')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_generation')
                    ->label('Angkatan')
                    ->options(fn () => NuirSubmission::query()->distinct()->pluck('year_generation', 'year_generation')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'revision' => 'Revision',
                        'content_ok' => 'Content OK',
                        'finalized' => 'Finalized',
                    ]),
                Tables\Filters\SelectFilter::make('reference_validation_status')
                    ->label('Progress Validasi')
                    ->options([
                        NuirSubmission::REF_VALIDATION_NOT_STARTED => 'Belum berprogress',
                        NuirSubmission::REF_VALIDATION_IN_PROGRESS => 'Berprogress',
                        NuirSubmission::REF_VALIDATION_COMPLETE => 'Selesai',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return match ($value) {
                            NuirSubmission::REF_VALIDATION_NOT_STARTED => $query->whereDoesntHave(
                                'references',
                                fn (Builder $referenceQuery) => $referenceQuery->whereNotNull('ref_approved'),
                            ),
                            NuirSubmission::REF_VALIDATION_COMPLETE => $query
                                ->whereHas('references')
                                ->whereDoesntHave(
                                    'references',
                                    fn (Builder $referenceQuery) => $referenceQuery->whereNull('ref_approved'),
                                ),
                            NuirSubmission::REF_VALIDATION_IN_PROGRESS => $query
                                ->whereHas(
                                    'references',
                                    fn (Builder $referenceQuery) => $referenceQuery->whereNotNull('ref_approved'),
                                )
                                ->whereHas(
                                    'references',
                                    fn (Builder $referenceQuery) => $referenceQuery->whereNull('ref_approved'),
                                ),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReferencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNuirSubmissions::route('/'),
            'view' => Pages\ViewNuirSubmission::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'references', 'assignment.validator'])
            ->withCount([
                'references as references_total_count',
                'references as references_validated_count' => fn (Builder $query) => $query->whereNotNull('ref_approved'),
            ])
            ->where('status', '!=', 'draft');
    }

    public static function referenceValidationStatusFromCounts(int $validated, int $total): string
    {
        if ($total === 0 || $validated === 0) {
            return NuirSubmission::REF_VALIDATION_NOT_STARTED;
        }

        if ($validated >= $total) {
            return NuirSubmission::REF_VALIDATION_COMPLETE;
        }

        return NuirSubmission::REF_VALIDATION_IN_PROGRESS;
    }

    public static function approvedReferenceCount(NuirSubmission $submission): int
    {
        return $submission->references()->where('ref_approved', true)->count();
    }

    public static function minimumApprovedReferences(NuirSubmission $submission): int
    {
        $setting = NuirSetting::where('year_generation', $submission->year_generation)->first();

        return $setting?->min_references_approved ?? 10;
    }
}
