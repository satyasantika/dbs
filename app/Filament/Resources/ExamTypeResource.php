<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesAdminPanelAccess;
use App\Filament\Resources\ExamTypeResource\Pages;
use App\Models\ExamType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamTypeResource extends Resource
{
    use AuthorizesAdminPanelAccess;

    protected static ?string $model = ExamType::class;


    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Jenis Ujian';

    protected static ?string $pluralModelLabel = 'Jenis Ujian';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Jenis Ujian')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label('Kode')
                    ->maxLength(50),
                Forms\Components\Toggle::make('active')
                    ->label('Aktif')
                    ->default(true),
            ]);
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
                        Tables\Columns\TextColumn::make('name')
                            ->label('Nama')
                            ->searchable()
                            ->sortable()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\IconColumn::make('active')
                            ->label('Aktif')
                            ->boolean()
                            ->grow(false),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('code')
                            ->label('Kode')
                            ->searchable()
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('formitems_count')
                            ->label('Item Form')
                            ->counts('formitems')
                            ->badge()
                            ->color('gray'),
                    ]),
                ])->space(2),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus')
                    ->visible(fn (ExamType $record): bool => ! static::isInUse($record)),
            ])
            ->bulkActions([]);
    }

    public static function isInUse(ExamType $record): bool
    {
        if (isset($record->has_registrations, $record->has_form_items)) {
            return (bool) $record->has_registrations || (bool) $record->has_form_items;
        }

        return $record->registrations()->exists() || $record->formitems()->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withExists([
                'registrations as has_registrations',
                'formitems as has_form_items',
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamTypes::route('/'),
            'create' => Pages\CreateExamType::route('/create'),
            'edit' => Pages\EditExamType::route('/{record}/edit'),
        ];
    }
}
