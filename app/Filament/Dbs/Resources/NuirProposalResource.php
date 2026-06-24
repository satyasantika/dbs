<?php

namespace App\Filament\Dbs\Resources;

use App\Filament\Concerns\AuthorizesDbsPanelAccess;
use App\Filament\Dbs\Resources\NuirProposalResource\Pages;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Services\NuirReviewService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NuirProposalResource extends Resource
{
    use AuthorizesDbsPanelAccess;

    protected static ?string $model = NuirProposal::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Manajemen NUIR';

    protected static ?string $modelLabel = 'Monitor Proposal';

    protected static ?string $pluralModelLabel = 'Monitor Proposal';

    protected static ?int $navigationSort = 3;

    protected static function dbsAccessPermission(): string
    {
        return 'review nuir submission';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submission.user.name')->label('Mahasiswa')->searchable(),
                Tables\Columns\TextColumn::make('submission.year_generation')->label('Angkatan'),
                Tables\Columns\TextColumn::make('submission.version')->label('Versi'),
                Tables\Columns\TextColumn::make('guide1.name')->label('Guide 1'),
                Tables\Columns\TextColumn::make('guide2.name')->label('Guide 2'),
                Tables\Columns\TextColumn::make('guide1_status')->label('Status 1')->badge(),
                Tables\Columns\TextColumn::make('guide2_status')->label('Status 2')->badge(),
                Tables\Columns\IconColumn::make('final')->label('Final')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_generation')
                    ->label('Angkatan')
                    ->options(fn () => NuirSubmission::query()
                        ->distinct()
                        ->orderBy('year_generation')
                        ->pluck('year_generation', 'year_generation')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->where(function (Builder $inner) use ($data) {
                            $inner->where('guide1_status', $data['value'])
                                ->orWhere('guide2_status', $data['value']);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('forceFinalize')
                    ->label('Force Finalize')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->visible(fn (NuirProposal $record) => $record->guide1_status === 'accepted'
                        && $record->guide2_status === 'accepted'
                        && ! $record->final)
                    ->requiresConfirmation()
                    ->action(function (NuirProposal $record): void {
                        app(NuirReviewService::class)->forceFinalize($record);
                        Notification::make()->success()->title('Proposal berhasil di-finalize.')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNuirProposals::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['submission.user', 'guide1', 'guide2']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
