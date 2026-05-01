<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelectionStageResource\Pages;
use App\Models\SelectionStage;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SelectionStageResource extends Resource
{
    protected static ?string $model = SelectionStage::class;


    protected static ?string $navigationGroup = 'Manajemen Seleksi';

    protected static ?string $modelLabel = 'Tahap Seleksi';

    protected static ?string $pluralModelLabel = 'Tahap Seleksi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Mahasiswa')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('stage_order')
                    ->label('Tahap Ke-')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('guide1_id')
                    ->label('Pembimbing 1 Terpilih')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Forms\Components\Select::make('guide2_id')
                    ->label('Pembimbing 2 Terpilih')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Forms\Components\Toggle::make('final')
                    ->label('Final / Selesai'),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Mahasiswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage_order')
                    ->label('Tahap')
                    ->sortable(),
                Tables\Columns\TextColumn::make('guide1.name')
                    ->label('Pembimbing 1'),
                Tables\Columns\TextColumn::make('guide2.name')
                    ->label('Pembimbing 2'),
                Tables\Columns\IconColumn::make('final')
                    ->label('Final')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('final')
                    ->label('Status Final'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSelectionStages::route('/'),
            'create' => Pages\CreateSelectionStage::route('/create'),
            'edit' => Pages\EditSelectionStage::route('/{record}/edit'),
        ];
    }
}
