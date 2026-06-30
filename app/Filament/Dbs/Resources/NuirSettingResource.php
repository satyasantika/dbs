<?php

namespace App\Filament\Dbs\Resources;

use App\Filament\Concerns\AuthorizesDbsPanelAccess;
use App\Filament\Concerns\NuirSettingFormSchema;
use App\Filament\Dbs\Resources\NuirSettingResource\Pages;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NuirSettingResource extends Resource
{
    use AuthorizesDbsPanelAccess;
    use NuirSettingFormSchema;

    protected static ?string $model = NuirSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Manajemen NUIR';

    protected static ?string $modelLabel = 'Konfigurasi NUIR';

    protected static ?string $pluralModelLabel = 'Konfigurasi NUIR';

    protected static ?int $navigationSort = 1;

    protected static function dbsAccessPermission(): string
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
                Tables\Columns\TextColumn::make('deadline')->label('Deadline')->date(),
                Tables\Columns\TextColumn::make('min_references_approved')->label('Min Ref'),
                Tables\Columns\TextColumn::make('max_references')->label('Max Ref'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (NuirSetting $record) => $record->active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (NuirSetting $record) => $record->active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->action(function (NuirSetting $record): void {
                        $record->update(['active' => ! $record->active]);
                        Notification::make()->success()->title('Status aktif diperbarui.')->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, NuirSetting $record): void {
                        if (NuirSubmission::where('year_generation', $record->year_generation)->exists()) {
                            Notification::make()
                                ->warning()
                                ->title('Setting tidak dapat dihapus karena masih ada submission terkait.')
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('year_generation', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNuirSettings::route('/'),
            'create' => Pages\CreateNuirSetting::route('/create'),
            'edit' => Pages\EditNuirSetting::route('/{record}/edit'),
        ];
    }
}
