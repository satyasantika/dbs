<?php

namespace App\Filament\NuirValidator\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\NuirValidator\Resources\NuirReferenceResource\Pages;
use App\Models\NuirReference;
use App\Support\NuirValidatorListReturn;
use App\Support\NuirValidatorReferenceStatus;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NuirReferenceResource extends Resource
{
    use AuthorizesNuirRolePanelAccess;

    public const DASHBOARD_VIEW_PENDING_REFERENCES = 'pending_references';

    public const DASHBOARD_VIEW_AWAITING_REVALIDATION = 'awaiting_revalidation';

    protected static ?string $model = NuirReference::class;

    protected static ?string $slug = 'nuir-references';

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Validasi NUIR';

    protected static ?string $modelLabel = 'Referensi';

    protected static ?string $pluralModelLabel = 'Validasi Referensi';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    protected static function nuirRoleAccessPermission(): string
    {
        return 'access dashboard validator nuir';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
            ])
            ->columns([
                // Dibungkus Layout\Stack — ini yang membuat Filament benar-benar
                // merender tiap baris sebagai card (hasColumnsLayout()), bukan
                // cuma ->contentGrid() saja.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('submission.user.name')
                            ->label('Mahasiswa')
                            ->searchable()
                            ->sortable()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('ref_order')
                            ->label('Referensi')
                            ->formatStateUsing(fn (int $state): string => '#'.$state)
                            ->sortable()
                            ->badge()
                            ->color('gray')
                            ->grow(false),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('submission.year_generation')
                            ->label('Angkatan')
                            ->sortable(),
                        Tables\Columns\TextColumn::make('submission.version')
                            ->label('Versi')
                            ->sortable(),
                        Tables\Columns\TextColumn::make('validation_status')
                            ->label('Status')
                            ->state(fn (): string => match (request()->query('view')) {
                                self::DASHBOARD_VIEW_AWAITING_REVALIDATION => 'Menunggu validasi ulang',
                                default => 'Pending',
                            })
                            ->badge()
                            ->color(fn (): string => match (request()->query('view')) {
                                self::DASHBOARD_VIEW_AWAITING_REVALIDATION => 'warning',
                                default => 'gray',
                            }),
                    ]),
                    Tables\Columns\TextColumn::make('activity_summary')
                        ->label('Aktivitas')
                        ->state(fn (NuirReference $record): string => NuirValidatorReferenceStatus::referenceActivitySummary($record))
                        ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('updated_at', $direction)),
                ])->space(2),
            ])
            ->actions([
                Tables\Actions\Action::make('validate')
                    ->label('Validasi')
                    ->icon('heroicon-o-eye')
                    ->url(fn (NuirReference $record): string => NuirSubmissionResource::viewUrl(
                        $record->nuir_submission_id,
                        $record->id,
                        NuirValidatorListReturn::referenceKey(request()->query('view')),
                        panel: 'nuir-validator',
                    )),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('15s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNuirReferences::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return static::assignedReferencesQuery()
            ->with(['submission.user', 'submission.assignment']);
    }

    public static function assignedReferencesQuery(?int $validatorId = null): Builder
    {
        $validatorId ??= auth()->id();

        return parent::getEloquentQuery()
            ->whereHas(
                'submission.assignment',
                fn (Builder $query) => $query->where('validator_id', $validatorId),
            );
    }

    public static function applyDashboardViewFilter(Builder $query, ?string $view): Builder
    {
        return match ($view) {
            self::DASHBOARD_VIEW_PENDING_REFERENCES => NuirValidatorReferenceStatus::pendingReferencesScope($query),
            self::DASHBOARD_VIEW_AWAITING_REVALIDATION => NuirValidatorReferenceStatus::awaitingRevalidationScope($query),
            default => $query,
        };
    }

    public static function dashboardViewLabel(?string $view): ?string
    {
        return match ($view) {
            self::DASHBOARD_VIEW_PENDING_REFERENCES => 'Referensi Pending',
            self::DASHBOARD_VIEW_AWAITING_REVALIDATION => 'Permintaan Revisi',
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
}
