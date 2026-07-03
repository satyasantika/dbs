<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesDbsPanelAccess;
use App\Filament\Resources\GuideGroupResource\Pages;
use App\Models\GuideAllocation;
use App\Models\GuideGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuideGroupResource extends Resource
{
    use AuthorizesDbsPanelAccess;

    protected static ?string $model = GuideGroup::class;

    protected static ?string $navigationGroup = 'Manajemen Seleksi';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $modelLabel = 'Kelompok Pembimbing';

    protected static ?string $pluralModelLabel = 'Kelompok Pembimbing';

    protected static ?int $navigationSort = 3;

    protected static function dbsAccessPermission(): string
    {
        return 'access selection/guide/groups';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('guide_allocation_id')
                ->label('Kuota Dosen')
                ->options(fn () => GuideAllocation::with('lecture')
                    ->get()
                    ->mapWithKeys(fn (GuideAllocation $allocation) => [
                        $allocation->id => ($allocation->lecture?->name ?? '-').' ('.$allocation->year.')',
                    ]))
                ->searchable()
                ->required()
                ->disabled(fn (?GuideGroup $record) => filled($record)),
            Forms\Components\TextInput::make('group')
                ->label('Kelompok')
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
            Forms\Components\Toggle::make('active')
                ->label('Aktif'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('allocation.lecture.name')->label('Dosen')->searchable(),
                Tables\Columns\TextColumn::make('group')->label('Kelompok'),
                Tables\Columns\TextColumn::make('guide1_quota')->label('Kuota P1'),
                Tables\Columns\TextColumn::make('guide2_quota')->label('Kuota P2'),
                Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (GuideGroup $record) => $record->active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (GuideGroup $record): string => $record->active
                        ? 'heroicon-o-x-circle'
                        : 'heroicon-o-check-circle')
                    ->color(fn (GuideGroup $record): string => $record->active ? 'danger' : 'success')
                    ->action(fn (GuideGroup $record) => $record->update(['active' => ! $record->active])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('group');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuideGroups::route('/'),
            'create' => Pages\CreateGuideGroup::route('/create'),
            'edit' => Pages\EditGuideGroup::route('/{record}/edit'),
        ];
    }
}
