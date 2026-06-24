<?php

namespace App\Filament\NuirValidator\Resources\NuirSubmissionResource\RelationManagers;

use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Models\NuirReference;
use App\Services\NuirAssignmentService;
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
                Tables\Columns\TextColumn::make('quote')->label('Kutipan')->limit(40)->wrap(),
                Tables\Columns\TextColumn::make('ref_approved')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        true, 1, '1' => 'Disetujui',
                        false, 0, '0' => 'Ditolak',
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
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible($canReview)
                    ->action(function (NuirReference $record): void {
                        app(NuirAssignmentService::class)->reviewReferenceAsValidator(
                            $record,
                            auth()->user(),
                            true,
                        );
                        Notification::make()->success()->title('Referensi disetujui.')->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible($canReview)
                    ->form([
                        Forms\Components\Textarea::make('ref_note')
                            ->label('Alasan penolakan')
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
                            Notification::make()->success()->title('Referensi ditolak.')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }
}
