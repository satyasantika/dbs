<?php

namespace App\Filament\NuirValidator\Resources\NuirSubmissionResource\RelationManagers;

use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Models\NuirReference;
use App\Services\NuirAssignmentService;
use App\Support\NuirReferenceExistence;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class ReferencesRelationManager extends RelationManager
{
    protected static string $relationship = 'references';

    protected static ?string $title = 'Referensi';

    public function table(Table $table): Table
    {
        $canReview = NuirSubmissionResource::canReviewReferences($this->getOwnerRecord());

        return $table
            ->recordTitleAttribute('ref_order')
            ->columns([
                Tables\Columns\TextColumn::make('ref_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('indexer_name')->label('Indexer'),
                Tables\Columns\TextColumn::make('link_ojs')
                    ->label('Link OJS')
                    ->limit(30)
                    ->url(fn (NuirReference $record) => $record->link_ojs, true)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('link_index')
                    ->label('Link Index')
                    ->limit(30)
                    ->url(fn (NuirReference $record) => $record->link_index, true)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('existence')
                    ->label('Eksistensi')
                    ->state(fn (NuirReference $record) => NuirReferenceExistence::isVerifiable($record) ? 'Lengkap' : 'Belum lengkap')
                    ->badge()
                    ->color(fn (NuirReference $record) => NuirReferenceExistence::isVerifiable($record) ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('quote')->label('Kutipan')->limit(40)->wrap(),
                Tables\Columns\TextColumn::make('ref_approved')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        true, 1, '1' => 'Disetujui',
                        false, 0, '0' => 'Diminta revisi',
                        default => 'Pending',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        true, 1, '1' => 'success',
                        false, 0, '0' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ref_note')->label('Catatan')->wrap(),
            ])
            ->defaultSort('ref_order')
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn (NuirReference $record) => 'Referensi #'.$record->ref_order)
                    ->infolist([
                        \Filament\Infolists\Components\TextEntry::make('link_ojs')->label('Link OJS')->url(fn ($record) => $record->link_ojs, true),
                        \Filament\Infolists\Components\TextEntry::make('indexer_name')->label('Indexer'),
                        \Filament\Infolists\Components\TextEntry::make('link_index')->label('Link Index')->url(fn ($record) => $record->link_index, true),
                        \Filament\Infolists\Components\TextEntry::make('link_drive')->label('Link Drive')->url(fn ($record) => $record->link_drive, true),
                        \Filament\Infolists\Components\TextEntry::make('quote')->label('Kutipan')->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('relevance')->label('Relevansi')->columnSpanFull(),
                    ]),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible($canReview)
                    ->action(function (NuirReference $record): void {
                        try {
                            app(NuirAssignmentService::class)->reviewReferenceAsValidator(
                                $record,
                                auth()->user(),
                                true,
                            );
                            Notification::make()->success()->title('Referensi disetujui.')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                        }
                    }),
                Tables\Actions\Action::make('requestRevision')
                    ->label('Minta Revisi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible($canReview)
                    ->form([
                        Forms\Components\Textarea::make('ref_note')
                            ->label('Catatan revisi')
                            ->required(),
                    ])
                    ->action(function (NuirReference $record, array $data): void {
                        try {
                            app(NuirAssignmentService::class)->reviewReferenceAsValidator(
                                $record,
                                auth()->user(),
                                false,
                                $data['ref_note'],
                            );
                            Notification::make()->success()->title('Permintaan revisi referensi disimpan.')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }
}
