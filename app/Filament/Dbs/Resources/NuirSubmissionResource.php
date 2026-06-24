<?php

namespace App\Filament\Dbs\Resources;

use App\Filament\Concerns\AuthorizesDbsPanelAccess;
use App\Filament\Dbs\Resources\NuirSubmissionResource\Pages;
use App\Filament\Dbs\Resources\NuirSubmissionResource\RelationManagers;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirReviewService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class NuirSubmissionResource extends Resource
{
    use AuthorizesDbsPanelAccess;

    protected static ?string $model = NuirSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationGroup = 'Manajemen NUIR';

    protected static ?string $modelLabel = 'Review Submission';

    protected static ?string $pluralModelLabel = 'Review Submission';

    protected static ?int $navigationSort = 2;

    protected static function dbsAccessPermission(): string
    {
        return 'review nuir submission';
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
                    Infolists\Components\TextEntry::make('dbs_note')->label('Catatan DBS')->columnSpanFull(),
                ])->columns(4),
            Infolists\Components\Section::make('Konten')
                ->schema([
                    Infolists\Components\TextEntry::make('title')->label('Judul')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('novelty')->label('Novelty')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('urgency')->label('Urgency')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('impact')->label('Impact')->columnSpanFull(),
                ]),
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
                Tables\Columns\TextColumn::make('updated_at')->label('Diperbarui')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_generation')
                    ->label('Angkatan')
                    ->options(fn () => NuirSubmission::query()->distinct()->pluck('year_generation', 'year_generation')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'revision' => 'Revision',
                        'content_ok' => 'Content OK',
                        'finalized' => 'Finalized',
                    ]),
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
        return parent::getEloquentQuery()->with(['user', 'references']);
    }

    public static function approvedReferenceCount(NuirSubmission $submission): int
    {
        return $submission->references()->where('ref_approved', true)->count();
    }

    public static function minimumApprovedReferences(NuirSubmission $submission): int
    {
        $setting = NuirSetting::where('year_generation', $submission->year_generation)->first();

        return $setting?->min_references_approved ?? 10;
    }

    public static function reviewSubmission(NuirSubmission $submission, string $action, ?string $dbsNote = null): void
    {
        try {
            app(NuirReviewService::class)->reviewSubmission($submission, $action, $dbsNote);
            Notification::make()->success()->title('Review submission disimpan.')->send();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? 'Validasi gagal.';
            Notification::make()->warning()->title($message)->send();
            throw $exception;
        }
    }
}
