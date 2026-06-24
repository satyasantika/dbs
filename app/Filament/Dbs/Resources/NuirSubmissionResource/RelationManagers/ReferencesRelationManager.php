<?php

namespace App\Filament\Dbs\Resources\NuirSubmissionResource\RelationManagers;

use App\Models\NuirReference;
use App\Services\NuirReviewService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class ReferencesRelationManager extends RelationManager
{
    protected static string $relationship = 'references';

    protected static ?string $title = 'Referensi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ref_order')
            ->columns([
                Tables\Columns\TextColumn::make('ref_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('indexer_name')->label('Indexer'),
                Tables\Columns\TextColumn::make('ref_approved')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        true, 1, '1' => 'Disetujui',
                        false, 0, '0' => 'Ditolak',
                        default => 'Pending',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        true, 1, '1' => 'success',
                        false, 0, '0' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ref_note')->label('Catatan')->wrap(),
            ])
            ->defaultSort('ref_order')
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (NuirReference $record): void {
                        app(NuirReviewService::class)->reviewReference($record, true);
                        Notification::make()->success()->title('Referensi disetujui.')->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('ref_note')
                            ->label('Alasan penolakan')
                            ->required(),
                    ])
                    ->action(function (NuirReference $record, array $data): void {
                        try {
                            app(NuirReviewService::class)->reviewReference($record, false, $data['ref_note']);
                            Notification::make()->success()->title('Referensi ditolak.')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }
}
