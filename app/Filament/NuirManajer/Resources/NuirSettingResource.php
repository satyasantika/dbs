<?php

namespace App\Filament\NuirManajer\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\Concerns\NuirSettingFormSchema;
use App\Filament\NuirManajer\Resources\NuirSettingResource\Pages;
use App\Models\NuirSetting;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NuirSettingResource extends Resource
{
    use AuthorizesNuirRolePanelAccess;
    use NuirSettingFormSchema;

    protected static ?string $model = NuirSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Manajemen NUIR';

    protected static ?string $modelLabel = 'Konfigurasi NUIR';

    protected static ?string $pluralModelLabel = 'Konfigurasi NUIR';

    protected static ?int $navigationSort = 2;

    protected static function nuirRoleAccessPermission(): string
    {
        return 'manage nuir settings';
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::nuirSettingFormSchema())->columns(2);
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
                        Tables\Columns\TextColumn::make('year_generation')
                            ->label('Angkatan')
                            ->sortable()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('stage')->label('Tahap')->sortable()->badge(),
                        Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean()->grow(false),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        // ->prefix() dipakai karena mode card tidak menampilkan
                        // header kolom seperti tabel.
                        Tables\Columns\TextColumn::make('min_references_approved')->label('Min Ref')->prefix('Min Ref: '),
                        Tables\Columns\TextColumn::make('max_references')->label('Max Ref')->prefix('Max Ref: '),
                        Tables\Columns\TextColumn::make('min_words_novelty')->label('Min N')->placeholder('—')->prefix('Min N: '),
                        Tables\Columns\TextColumn::make('max_words_novelty')->label('Max N')->placeholder('—')->prefix('Max N: '),
                    ]),
                ])->space(2),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('year_generation', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNuirSettings::route('/'),
            'create' => Pages\CreateNuirSettings::route('/create'),
            'edit' => Pages\EditNuirSettings::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
