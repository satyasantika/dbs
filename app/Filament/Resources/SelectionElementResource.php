<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelectionElementResource\Pages;
use App\Models\SelectionElement;
use App\Models\SelectionStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SelectionElementResource extends Resource
{
    protected static ?string $model = SelectionElement::class;


    protected static ?string $navigationGroup = 'Manajemen Seleksi';

    protected static ?string $modelLabel = 'Elemen NUIR';

    protected static ?string $pluralModelLabel = 'Elemen NUIR';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('selection_stage_id')
                    ->label('Tahap Seleksi')
                    ->options(fn () => SelectionStage::with('student')->get()->mapWithKeys(fn ($s) => [$s->id => ($s->student->name ?? '-') . ' - Tahap ' . $s->stage_order]))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('element')
                    ->label('Elemen')
                    ->options([
                        'title' => 'Judul',
                        'novelty' => 'Kebaruan (Novelty)',
                        'urgency' => 'Urgensi',
                        'impact' => 'Dampak',
                        'references' => 'Referensi',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('link')
                    ->label('Link')
                    ->url()
                    ->maxLength(500),
                Forms\Components\Select::make('approved')
                    ->label('Status')
                    ->options([
                        '1' => 'Disetujui',
                        '0' => 'Ditolak',
                    ])
                    ->nullable(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage.student.name')
                    ->label('Mahasiswa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stage.stage_order')
                    ->label('Tahap'),
                Tables\Columns\TextColumn::make('element')
                    ->label('Elemen')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'title' => 'Judul',
                        'novelty' => 'Kebaruan',
                        'urgency' => 'Urgensi',
                        'impact' => 'Dampak',
                        'references' => 'Referensi',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
                Tables\Columns\IconColumn::make('approved')
                    ->label('Disetujui')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('element')
                    ->label('Elemen')
                    ->options([
                        'title' => 'Judul',
                        'novelty' => 'Kebaruan',
                        'urgency' => 'Urgensi',
                        'impact' => 'Dampak',
                        'references' => 'Referensi',
                    ]),
                Tables\Filters\TernaryFilter::make('approved')
                    ->label('Status Persetujuan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSelectionElements::route('/'),
            'create' => Pages\CreateSelectionElement::route('/create'),
            'edit' => Pages\EditSelectionElement::route('/{record}/edit'),
        ];
    }
}
