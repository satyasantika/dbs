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
            ->contentGrid([
                'default' => 1,
            ])
            ->columns([
                // Dibungkus Layout\Stack — ini yang membuat Filament benar-benar
                // merender tiap baris sebagai card (hasColumnsLayout()), bukan
                // cuma ->contentGrid() saja.
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('ref_order')
                            ->label('#')
                            ->sortable()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->grow(false),
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
                    ]),
                    Tables\Columns\TextColumn::make('ref_note')->label('Catatan')->wrap(),
                ])->space(2),
            ])
            ->defaultSort('ref_order')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
