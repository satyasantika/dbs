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
            ->columns([
                Tables\Columns\TextColumn::make('year_generation')->label('Angkatan')->sortable(),
                Tables\Columns\TextColumn::make('stage')->label('Tahap')->sortable(),
                Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('min_references_approved')->label('Min Ref'),
                Tables\Columns\TextColumn::make('max_references')->label('Max Ref'),
                Tables\Columns\TextColumn::make('min_words_novelty')->label('Min N')->placeholder('—'),
                Tables\Columns\TextColumn::make('max_words_novelty')->label('Max N')->placeholder('—'),
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
