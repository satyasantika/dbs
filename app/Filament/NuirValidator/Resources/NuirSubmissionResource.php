<?php

namespace App\Filament\NuirValidator\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource\Pages;
use App\Models\NuirSubmission;
use App\Support\NuirValidatorListReturn;
use App\Support\NuirValidatorReferenceStatus;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NuirSubmissionResource extends Resource
{
    use AuthorizesNuirRolePanelAccess;

    public const DASHBOARD_VIEW_ASSIGNED = 'assigned';

    public const DASHBOARD_VIEW_VALIDATION_COMPLETE = 'validation_complete';

    protected static ?string $model = NuirSubmission::class;

    protected static ?string $slug = 'nuir-submissions';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Validasi NUIR';

    protected static ?string $modelLabel = 'Submission NUIR';

    protected static ?string $pluralModelLabel = 'Submission Ditugaskan';

    protected static ?int $navigationSort = 1;

    protected static function nuirRoleAccessPermission(): string
    {
        return 'access dashboard validator nuir';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Mahasiswa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('year_generation')->label('Angkatan')->sortable(),
                Tables\Columns\TextColumn::make('version')->label('Versi')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => request()->query('view') === self::DASHBOARD_VIEW_VALIDATION_COMPLETE
                        ? 'Disetujui'
                        : $state)
                    ->color(fn (string $state): string => request()->query('view') === self::DASHBOARD_VIEW_VALIDATION_COMPLETE
                        ? 'success'
                        : 'gray'),
                Tables\Columns\ViewColumn::make('reference_breakdown')
                    ->label('Referensi')
                    ->view('filament.nuir-validator.tables.reference-breakdown-badges')
                    ->viewData(fn (NuirSubmission $record): array => [
                        'badges' => NuirValidatorReferenceStatus::referenceBreakdownBadges($record),
                    ])
                    ->visible(fn (): bool => request()->query('view', self::DASHBOARD_VIEW_ASSIGNED) === self::DASHBOARD_VIEW_ASSIGNED),
                Tables\Columns\TextColumn::make('activity_summary')
                    ->label('Aktivitas')
                    ->state(fn (NuirSubmission $record): string => NuirValidatorReferenceStatus::submissionActivitySummary($record))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('updated_at', $direction)),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Validasi')
                    ->icon('heroicon-o-eye')
                    ->url(fn (NuirSubmission $record): string => static::viewUrl(
                        $record,
                        returnTo: NuirValidatorListReturn::submissionKey(request()->query('view')),
                        panel: 'nuir-validator',
                    )),
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
        $validatorId = auth()->id();

        return parent::getEloquentQuery()
            ->with(['user', 'references', 'assignment'])
            ->whereHas('assignment', fn (Builder $query) => $query->where('validator_id', $validatorId));
    }

    public static function applyDashboardViewFilter(Builder $query, ?string $view): Builder
    {
        return match ($view) {
            self::DASHBOARD_VIEW_VALIDATION_COMPLETE => NuirValidatorReferenceStatus::validationCompleteSubmissionsScope($query),
            default => $query,
        };
    }

    public static function dashboardViewLabel(?string $view): ?string
    {
        return match ($view) {
            self::DASHBOARD_VIEW_ASSIGNED => 'Submission Ditugaskan',
            self::DASHBOARD_VIEW_VALIDATION_COMPLETE => 'Validasi Selesai',
            default => null,
        };
    }

    public static function listUrl(?string $view = null, string $panel = 'nuir-validator'): string
    {
        $url = static::getUrl('index', panel: $panel);

        if (filled($view)) {
            return $url.'?view='.urlencode($view);
        }

        return $url;
    }

    public static function viewUrl(
        NuirSubmission|int $record,
        ?int $referenceId = null,
        ?string $returnTo = null,
        string $panel = 'nuir-validator',
    ): string {
        $url = static::getUrl('view', ['record' => $record], panel: $panel);

        $query = array_filter([
            'reference' => filled($referenceId) ? (string) $referenceId : null,
            'return' => filled($returnTo) ? $returnTo : null,
        ]);

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }

    public static function canReviewReferences(NuirSubmission $submission): bool
    {
        return $submission->isValidatorReviewable();
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}
