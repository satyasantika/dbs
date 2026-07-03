<?php

namespace App\Filament\NuirManajer\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirAssignmentService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class NuirSubmissionResource extends Resource
{
    use AuthorizesNuirRolePanelAccess;

    public const DASHBOARD_VIEW_UNASSIGNED = 'unassigned';

    public const DASHBOARD_VIEW_SUBMITTED = 'submitted';

    public const DASHBOARD_VIEW_REVISION = 'revision';

    public const DASHBOARD_VIEW_CONTENT_OK = 'content_ok';

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
                Tables\Columns\SelectColumn::make('assignment.validator_id')
                    ->label('Validator')
                    ->placeholder('Belum ditugaskan')
                    ->options(fn () => app(NuirAssignmentService::class)->validators()->pluck('name', 'id'))
                    ->getStateUsing(fn (NuirSubmission $record): ?int => $record->assignment?->validator_id)
                    ->updateStateUsing(function (NuirSubmission $record, $state) {
                        if (blank($state)) {
                            return null;
                        }

                        $validator = User::find($state);

                        if (! $validator) {
                            return null;
                        }

                        try {
                            app(NuirAssignmentService::class)->assignValidator($record, $validator, auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Validator berhasil didelegasikan.')
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title(collect($exception->errors())->flatten()->first())
                                ->send();
                        }

                        return $state;
                    })
                    ->visible(fn (): bool => auth()->user()?->can('delegate nuir validator') ?? false),
                Tables\Columns\TextColumn::make('assignment.validator.name')
                    ->label('Validator')
                    ->placeholder('Belum ditugaskan')
                    ->visible(fn (): bool => ! (auth()->user()?->can('delegate nuir validator') ?? false)),
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
                Tables\Columns\TextColumn::make('updated_at')->label('Diperbarui')->since()->sortable(),
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
            ->bulkActions([
                Tables\Actions\BulkAction::make('delegateValidator')
                    ->label('Delegasikan Validator')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('validator_id')
                            ->label('Validator NUIR')
                            ->options(fn () => app(NuirAssignmentService::class)->validators()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $validator = User::find($data['validator_id']);

                        if (! $validator) {
                            return;
                        }

                        $errors = collect();
                        $delegated = 0;

                        foreach ($records as $record) {
                            try {
                                app(NuirAssignmentService::class)->assignValidator($record, $validator, auth()->user());
                                $delegated++;
                            } catch (ValidationException $exception) {
                                $errors->push($record->user?->name.': '.collect($exception->errors())->flatten()->first());
                            }
                        }

                        if ($delegated > 0) {
                            Notification::make()
                                ->success()
                                ->title('Validator berhasil didelegasikan ke '.$delegated.' submission.')
                                ->send();
                        }

                        if ($errors->isNotEmpty()) {
                            Notification::make()
                                ->danger()
                                ->title('Sebagian submission gagal didelegasikan')
                                ->body($errors->implode("\n"))
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => auth()->user()?->can('delegate nuir validator') ?? false),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('15s');
    }

    public static function getRelations(): array
    {
        return [];
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
        return static::activeSubmissionsQuery()
            ->with(['user', 'references', 'assignment.validator'])
            ->withCount([
                'references as references_total_count',
                'references as references_validated_count' => fn (Builder $query) => $query->whereNotNull('ref_approved'),
            ]);
    }

    public static function activeSubmissionsQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', '!=', 'draft')
            ->whereDoesntHave('childSubmissions', fn (Builder $query) => $query->where('status', '!=', 'draft'));
    }

    public static function applyDashboardViewFilter(Builder $query, ?string $view): Builder
    {
        return match ($view) {
            self::DASHBOARD_VIEW_UNASSIGNED => $query->whereDoesntHave('assignment'),
            self::DASHBOARD_VIEW_SUBMITTED => $query->where('status', 'submitted'),
            self::DASHBOARD_VIEW_REVISION => $query->where('status', 'revision'),
            self::DASHBOARD_VIEW_CONTENT_OK => $query->where('status', 'content_ok'),
            default => $query,
        };
    }

    public static function dashboardViewLabel(?string $view): ?string
    {
        return match ($view) {
            self::DASHBOARD_VIEW_UNASSIGNED => 'Belum Didelegasikan',
            self::DASHBOARD_VIEW_SUBMITTED => 'Menunggu Review',
            self::DASHBOARD_VIEW_REVISION => 'Diminta Revisi',
            self::DASHBOARD_VIEW_CONTENT_OK => 'Konten Disetujui',
            default => null,
        };
    }

    public static function listUrl(?string $view = null, string $panel = 'nuir-manajer'): string
    {
        $url = static::getUrl('index', panel: $panel);

        if (filled($view)) {
            return $url.'?view='.urlencode($view);
        }

        return $url;
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
