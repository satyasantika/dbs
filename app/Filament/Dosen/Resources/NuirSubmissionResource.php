<?php

namespace App\Filament\Dosen\Resources;

use App\Filament\Dosen\Resources\NuirSubmissionResource\Pages;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NuirSubmissionResource extends Resource
{
    protected static ?string $model = NuirSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Menu usulan NUIR';

    protected static ?string $modelLabel = 'Usulan NUIR';

    protected static ?string $pluralModelLabel = 'Usulan NUIR';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('respond nuir proposal') ?? false;
    }

    public static function table(Table $table): Table
    {
        $userId = auth()->id();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Mahasiswa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('year_generation')->label('Angkatan')->sortable(),
                Tables\Columns\TextColumn::make('version')->label('Versi')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('seat')
                    ->label('Kursi Saya')
                    ->state(function (NuirSubmission $record) use ($userId): string {
                        $seat = static::seatForUser($record, $userId);

                        return $seat === null ? '—' : $seat['label'].': '.$seat['statusLabel'];
                    })
                    ->badge()
                    ->color(function (NuirSubmission $record) use ($userId): string {
                        return static::seatForUser($record, $userId)['color'] ?? 'gray';
                    }),
                Tables\Columns\TextColumn::make('references_validated_count')
                    ->label('Referensi Divalidasi')
                    ->formatStateUsing(fn ($state, NuirSubmission $record): string => ($state ?? 0).'/'.($record->references_total_count ?? 0)),
                Tables\Columns\TextColumn::make('updated_at')->label('Diperbarui')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_generation')
                    ->label('Angkatan')
                    ->options(fn () => NuirSubmission::query()->distinct()->pluck('year_generation', 'year_generation')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
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

    /**
     * @return array{label: string, statusLabel: string, color: string}|null
     */
    public static function seatForUser(NuirSubmission $record, int $userId): ?array
    {
        /** @var NuirProposal|null $proposal */
        $proposal = $record->relationLoaded('proposals')
            ? $record->proposals->sortByDesc('id')->first()
            : $record->proposals()->latest('id')->first();

        if (! $proposal) {
            return null;
        }

        $seat = match ($userId) {
            $proposal->guide1_id => 1,
            $proposal->guide2_id => 2,
            default => null,
        };

        if ($seat === null) {
            return null;
        }

        $status = $seat === 1 ? $proposal->guide1_status : $proposal->guide2_status;

        return [
            'label' => 'P'.$seat,
            'statusLabel' => match ($status) {
                'accepted' => 'Diterima',
                'rejected' => 'Ditolak',
                default => 'Menunggu',
            },
            'color' => match ($status) {
                'accepted' => 'success',
                'rejected' => 'danger',
                default => 'warning',
            },
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
        $userId = auth()->id();

        return parent::getEloquentQuery()
            ->where('status', '!=', 'draft')
            ->whereDoesntHave('childSubmissions', fn (Builder $query) => $query->where('status', '!=', 'draft'))
            ->whereHas('proposals', fn (Builder $query) => $query->where(
                fn (Builder $q) => $q->where('guide1_id', $userId)->orWhere('guide2_id', $userId)
            ))
            ->with(['user', 'references', 'proposals' => fn ($query) => $query->with(['guide1', 'guide2'])->orderByDesc('id')])
            ->withCount([
                'references as references_total_count',
                'references as references_validated_count' => fn (Builder $query) => $query->whereNotNull('ref_approved'),
            ]);
    }
}
