<?php

namespace App\Filament\Dosen\Pages;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Resources\Components\Tab;
use Filament\Resources\Concerns\HasTabs;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class Scoring extends Page implements HasTable
{
    use HasTabs;
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $title = 'Penilaian Ujian';

    protected static ?string $slug = 'examination/scoring';

    protected static string $view = 'filament.dosen.pages.scoring';

    #[Url]
    public ?string $activeTab = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access examination/scoring') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'dosen', $tenant);
    }

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'unscored';
    }

    public function getTabs(): array
    {
        $userId = auth()->id();

        $unscoredCount = ExamScore::query()
            ->where('exam_scores.user_id', $userId)
            ->whereNull('exam_scores.grade')
            ->count();

        $totalCount = ExamScore::query()
            ->where('exam_scores.user_id', $userId)
            ->count();

        return [
            'unscored' => Tab::make('Belum Dinilai')
                ->badge($unscoredCount)
                ->badgeColor($unscoredCount > 0 ? 'warning' : 'success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('exam_scores.grade')),
            'all' => Tab::make('Keseluruhan')
                ->badge($totalCount),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(Dashboard::getUrl()),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return ExamScore::query()
            ->with(['registration.student', 'registration.examtype'])
            ->where('exam_scores.user_id', auth()->id())
            ->orderByDesc(
                ExamRegistration::query()
                    ->select('exam_date')
                    ->whereColumn('exam_registrations.id', 'exam_scores.exam_registration_id')
                    ->limit(1)
            );
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseTable()
            ->query(fn (): Builder => $this->getTableQuery())
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...));
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('registration.student.name')
                    ->label('Mahasiswa')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->whereHas('registration.student', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('registration.examtype', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration.examtype.name')
                    ->label('Jenis Ujian')
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        str_contains(strtolower($state ?? ''), 'sempro') => 'success',
                        str_contains(strtolower($state ?? ''), 'semhas') => 'info',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('registration.exam_date')
                    ->label('Tanggal Ujian')
                    ->date('d M Y')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            ExamRegistration::query()
                                ->select('exam_date')
                                ->whereColumn('exam_registrations.id', 'exam_scores.exam_registration_id')
                                ->limit(1),
                            $direction
                        );
                    }),
                Tables\Columns\TextColumn::make('registration.exam_time')
                    ->label('Waktu')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('letter')
                    ->label('Nilai')
                    ->placeholder('Belum dinilai')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'primary' : 'danger'),
                Tables\Columns\IconColumn::make('pass_approved')
                    ->label('Lulus')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('revision')
                    ->label('Revisi')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('revision_note')
                    ->label('Catatan')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('score')
                    ->label('Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (ExamScore $record): string => route('scoring.edit', $record)),
                Tables\Actions\Action::make('file')
                    ->label('File')
                    ->icon('heroicon-o-document')
                    ->color('gray')
                    ->url(fn (ExamScore $record): ?string => $record->registration?->exam_file)
                    ->openUrlInNewTab()
                    ->visible(fn (ExamScore $record): bool => filled($record->registration?->exam_file)),
                Tables\Actions\Action::make('whatsapp')
                    ->label('WA')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (ExamScore $record): ?string {
                        $phone = $record->registration?->student?->phone;

                        if (blank($phone)) {
                            return null;
                        }

                        return 'https://api.whatsapp.com/send/?phone=62'.$phone;
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (ExamScore $record): bool => filled($record->registration?->student?->phone)),
                Tables\Actions\Action::make('chief')
                    ->label('Ketua')
                    ->icon('heroicon-o-user-circle')
                    ->color('gray')
                    ->url(fn (ExamScore $record): string => route('chief.show', $record->exam_registration_id))
                    ->visible(fn (ExamScore $record): bool => $record->user_id === $record->registration?->chief_id),
            ])
            ->emptyStateHeading(fn (): string => $this->activeTab === 'unscored'
                ? 'Semua penilaian sudah diinput'
                : 'Belum ada penugasan penilaian')
            ->emptyStateDescription(fn (): string => $this->activeTab === 'unscored'
                ? 'Tidak ada ujian yang menunggu penilaian Anda.'
                : 'Penilaian ujian akan muncul setelah admin menetapkan Anda sebagai penguji.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->paginated([10, 25, 50]);
    }
}
