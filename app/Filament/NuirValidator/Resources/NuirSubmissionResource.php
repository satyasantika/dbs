<?php

namespace App\Filament\NuirValidator\Resources;

use App\Filament\Concerns\AuthorizesNuirRolePanelAccess;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource\Pages;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource\RelationManagers;
use App\Models\NuirSubmission;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NuirSubmissionResource extends Resource
{
    use AuthorizesNuirRolePanelAccess;

    protected static ?string $model = NuirSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Validasi NUIR';

    protected static ?string $modelLabel = 'Validasi Referensi';

    protected static ?string $pluralModelLabel = 'Validasi Referensi';

    protected static ?int $navigationSort = 1;

    protected static function nuirRoleAccessPermission(): string
    {
        return 'access dashboard validator nuir';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Ringkasan')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label('Mahasiswa'),
                    Infolists\Components\TextEntry::make('year_generation')->label('Angkatan'),
                    Infolists\Components\TextEntry::make('version')->label('Versi'),
                    Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                    Infolists\Components\TextEntry::make('title')->label('Judul')->columnSpanFull(),
                ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Mahasiswa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('year_generation')->label('Angkatan')->sortable(),
                Tables\Columns\TextColumn::make('version')->label('Versi')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('assignment.assigned_at')->label('Ditugaskan')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Diperbarui')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReferencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNuirSubmissions::route('/'),
            'view' => Pages\ViewNuirSubmission::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $validatorId = auth()->id();

        return parent::getEloquentQuery()
            ->with(['user', 'references', 'assignment'])
            ->whereHas('assignment', fn (Builder $query) => $query->where('validator_id', $validatorId));
    }

    public static function canReviewReferences(NuirSubmission $submission): bool
    {
        return $submission->isValidatorReviewable();
    }
}
