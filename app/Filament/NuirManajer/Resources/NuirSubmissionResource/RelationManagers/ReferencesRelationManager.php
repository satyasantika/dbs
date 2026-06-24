<?php

namespace App\Filament\NuirManajer\Resources\NuirSubmissionResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
                    ->label('Status Validator')
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
            ->actions([])
            ->bulkActions([]);
    }
}
