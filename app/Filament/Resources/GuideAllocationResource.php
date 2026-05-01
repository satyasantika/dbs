<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuideAllocationResource\Pages;
use App\Models\GuideAllocation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuideAllocationResource extends Resource
{
    protected static ?string $model = GuideAllocation::class;


    protected static ?string $navigationGroup = 'Manajemen Seleksi';

    protected static ?string $modelLabel = 'Kuota Pembimbing';

    protected static ?string $pluralModelLabel = 'Kuota Pembimbing';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Dosen')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('guide1_quota')
                    ->label('Kuota Pembimbing 1')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('guide2_quota')
                    ->label('Kuota Pembimbing 2')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('examiner_quota')
                    ->label('Kuota Penguji')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('active')
                    ->label('Aktif')
                    ->default(false),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lecture.name')
                    ->label('Dosen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
                Tables\Columns\TextColumn::make('guide1_quota')
                    ->label('Kuota P1'),
                Tables\Columns\TextColumn::make('guide2_quota')
                    ->label('Kuota P2'),
                Tables\Columns\TextColumn::make('examiner_quota')
                    ->label('Kuota Penguji'),
                Tables\Columns\IconColumn::make('active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status Aktif'),
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn () => GuideAllocation::distinct()->pluck('year', 'year')),
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
            ->defaultSort('year', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuideAllocations::route('/'),
            'create' => Pages\CreateGuideAllocation::route('/create'),
            'edit' => Pages\EditGuideAllocation::route('/{record}/edit'),
        ];
    }
}
