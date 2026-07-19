<?php

namespace App\Filament\NuirManajer\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;
use App\Models\NuirProposal;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirAssignmentService;
use App\Services\NuirService;
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
            ->contentGrid([
                'default' => 1,
            ])
            ->columns([
                // Dibungkus Layout\Stack — ini yang membuat Filament benar-benar
                // merender tiap baris sebagai card (hasColumnsLayout()), bukan
                // cuma ->contentGrid() saja.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('user.name')
                            ->label('Mahasiswa')
                            ->searchable()
                            ->sortable()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('status')
                            ->label('Status')
                            ->badge()
                            ->grow(false),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('year_generation')->label('Angkatan')->sortable(),
                        Tables\Columns\TextColumn::make('version')->label('Versi')->sortable(),
                        Tables\Columns\TextColumn::make('updated_at')->label('Diperbarui')->since()->sortable(),
                    ]),
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
                    Tables\Columns\ViewColumn::make('approval_status')
                        ->label('Persetujuan')
                        ->view('filament.nuir-manajer.tables.approval-status-lines')
                        ->viewData(fn (NuirSubmission $record): array => [
                            'lines' => static::approvalStatusLines($record),
                        ]),
                ])->space(2),
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
                Tables\Actions\BulkAction::make('finalizeGuideBulk')
                    ->label('Tetapkan Pembimbing')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Tetapkan Pembimbing untuk submission terpilih?')
                    ->modalDescription('Hanya submission dengan status content_ok dan kedua kursi pembimbing sudah accepted yang akan diproses.')
                    ->action(function (Collection $records): void {
                        $finalized = 0;

                        foreach ($records as $record) {
                            if ($record->status !== 'content_ok') {
                                continue;
                            }

                            $proposal = $record->proposals()->where('final', false)->latest('id')->first();

                            if (! $proposal || ! $proposal->isBothAccepted()) {
                                continue;
                            }

                            app(NuirService::class)->finalizeProposal($proposal);
                            $finalized++;
                        }

                        Notification::make()
                            ->success()
                            ->title($finalized > 0
                                ? "Pembimbing berhasil ditetapkan untuk {$finalized} submission."
                                : 'Tidak ada submission yang memenuhi syarat.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => auth()->user()?->can('finalize nuir guide') ?? false),
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
            ->with([
                'user',
                'references',
                'assignment.validator',
                'proposals' => fn ($query) => $query->with(['guide1', 'guide2'])->orderByDesc('id'),
            ]);
    }

    public static function latestProposal(NuirSubmission $submission): ?NuirProposal
    {
        return $submission->relationLoaded('proposals')
            ? $submission->proposals->sortByDesc('id')->first()
            : $submission->proposals()->latest('id')->first();
    }

    /**
     * Baris "Persetujuan": status P1, status P2, lalu progres validasi referensi.
     * isBothAccepted() tidak lagi ditampilkan sebagai badge tersendiri di sini —
     * tetap dipakai sebagai syarat pengesahan pasangan pembimbing (lihat
     * finalizeGuideBulk & ViewNuirSubmission::finalizeGuideAction()).
     *
     * @return list<array{label: string, color: string}>
     */
    public static function approvalStatusLines(NuirSubmission $submission): array
    {
        $proposal = static::latestProposal($submission);

        return [
            static::guideApprovalLine($proposal, 1),
            static::guideApprovalLine($proposal, 2),
            static::referenceValidationLine($submission),
        ];
    }

    /**
     * @return array{label: string, color: string}
     */
    private static function guideApprovalLine(?NuirProposal $proposal, int $seat): array
    {
        $guideId = $seat === 1 ? $proposal?->guide1_id : $proposal?->guide2_id;

        if (! $guideId) {
            return ['label' => "P{$seat}: Belum diusulkan", 'color' => 'gray'];
        }

        $guide = $seat === 1 ? $proposal->guide1 : $proposal->guide2;
        $status = $seat === 1 ? $proposal->guide1_status : $proposal->guide2_status;
        $initial = $guide?->initial ?: $guide?->name ?? '—';

        return [
            'label' => "{$initial} (P{$seat}): ".static::guideSeatShortLabel($status),
            'color' => match ($status) {
                'accepted' => 'success',
                'rejected' => 'danger',
                default => 'warning',
            },
        ];
    }

    /**
     * @return array{label: string, color: string}
     */
    private static function referenceValidationLine(NuirSubmission $submission): array
    {
        return [
            'label' => 'Referensi divalidasi: '.$submission->referenceValidationProgressLabel(),
            'color' => NuirSubmission::referenceValidationStatusColor($submission->referenceValidationStatus()),
        ];
    }

    private static function guideSeatShortLabel(?string $status): string
    {
        return match ($status) {
            'accepted' => 'ACC',
            'rejected' => 'Menolak',
            default => 'Menunggu',
        };
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
