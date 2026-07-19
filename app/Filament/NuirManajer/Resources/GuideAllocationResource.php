<?php

namespace App\Filament\NuirManajer\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\NuirManajer\Resources\GuideAllocationResource\Pages;
use App\Models\GuideAllocation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class GuideAllocationResource extends Resource
{
    use AuthorizesNuirRolePanelAccess;

    protected static ?string $model = GuideAllocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Manajemen NUIR';

    protected static ?string $modelLabel = 'Kuota Pembimbing';

    protected static ?string $pluralModelLabel = 'Kuota Pembimbing';

    protected static ?int $navigationSort = 3;

    protected static function nuirRoleAccessPermission(): string
    {
        return 'manage nuir guide quota';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Dosen')
                    ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('guide1_quota')
                    ->label('Kuota Pembimbing 1')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('guide2_quota')
                    ->label('Kuota Pembimbing 2')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('active')
                    ->label('Aktif')
                    ->default(true),
            ])->columns(2);
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
                        Tables\Columns\TextColumn::make('lecture.name')
                            ->label('Dosen')
                            ->searchable()
                            ->sortable()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('year')
                            ->label('Tahun')
                            ->sortable()
                            ->badge()
                            ->color('gray'),
                        Tables\Columns\IconColumn::make('active')
                            ->label('Aktif')
                            ->boolean()
                            ->grow(false),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        // ->prefix() dipakai karena mode card tidak menampilkan
                        // header kolom seperti tabel — tanpa ini angka Kuota/
                        // Terisi jadi ambigu tanpa konteks.
                        Tables\Columns\TextColumn::make('guide1_quota')
                            ->label('Kuota P1')
                            ->prefix('Kuota P1: '),
                        Tables\Columns\TextColumn::make('guide1_filled')
                            ->label('Terisi P1')
                            ->prefix('Terisi P1: '),
                        Tables\Columns\TextColumn::make('guide2_quota')
                            ->label('Kuota P2')
                            ->prefix('Kuota P2: '),
                        Tables\Columns\TextColumn::make('guide2_filled')
                            ->label('Terisi P2')
                            ->prefix('Terisi P2: '),
                    ]),
                ])->space(2),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn (): array => GuideAllocation::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year')
                        ->mapWithKeys(fn ($year) => [(string) $year => (string) $year])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->default(function (): ?string {
                        $year = GuideAllocation::query()->max('year');

                        return $year !== null ? (string) $year : null;
                    })
                    ->indicator('Tahun'),
                Tables\Filters\TernaryFilter::make('active')->label('Status Aktif'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (GuideAllocation $record): string => $record->active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (GuideAllocation $record): string => $record->active
                        ? 'heroicon-o-x-circle'
                        : 'heroicon-o-check-circle')
                    ->color(fn (GuideAllocation $record): string => $record->active ? 'danger' : 'success')
                    ->action(function (GuideAllocation $record): void {
                        $activating = ! $record->active;
                        $record->update(['active' => $activating]);

                        Notification::make()
                            ->success()
                            ->title($activating ? 'Kuota diaktifkan' : 'Kuota dinonaktifkan')
                            ->body(($record->lecture?->name ?? 'Dosen').' · tahun '.$record->year)
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus kuota pembimbing')
                    ->modalDescription(fn (GuideAllocation $record): string => 'Yakin hapus kuota '
                        .($record->lecture?->name ?? 'dosen')
                        .' tahun '
                        .$record->year
                        .'? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Hapus')
                    ->successNotification(
                        fn (GuideAllocation $record) => Notification::make()
                            ->success()
                            ->title('Kuota dihapus')
                            ->body(($record->lecture?->name ?? 'Dosen').' · tahun '.$record->year.' berhasil dihapus.'),
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan kuota terpilih')
                        ->modalDescription(fn (Collection $records): string => 'Aktifkan '
                            .$records->count()
                            .' baris kuota pembimbing? Dosen terpilih akan muncul kembali di alokasi NUIR.')
                        ->action(function (Collection $records): void {
                            $records->each(fn (GuideAllocation $record) => $record->update(['active' => true]));

                            Notification::make()
                                ->success()
                                ->title('Kuota diaktifkan')
                                ->body($records->count().' baris diubah menjadi aktif.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan kuota terpilih')
                        ->modalDescription(fn (Collection $records): string => 'Nonaktifkan '
                            .$records->count()
                            .' baris kuota pembimbing? Dosen terpilih tidak akan ditawarkan di alokasi NUIR.')
                        ->action(function (Collection $records): void {
                            $records->each(fn (GuideAllocation $record) => $record->update(['active' => false]));

                            Notification::make()
                                ->success()
                                ->title('Kuota dinonaktifkan')
                                ->body($records->count().' baris diubah menjadi nonaktif.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('year', 'desc');
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuideAllocations::route('/'),
            'import' => Pages\ImportGuideAllocations::route('/import'),
            'create' => Pages\CreateGuideAllocation::route('/create'),
            'edit' => Pages\EditGuideAllocation::route('/{record}/edit'),
        ];
    }
}
