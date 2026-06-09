<?php

namespace App\Filament\Dosen\Pages;

use App\Filament\Dosen\Concerns\HasDosenScoringRecap;
use App\Models\ExamScore;
use App\Services\Examination\DosenScoringPresenter;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnscoredScoring extends Page implements HasTable
{
    use HasDosenScoringRecap;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $title = 'Ujian Belum Selesai Dinilai';

    protected static ?string $slug = 'examination/scoring';

    protected static string $view = 'filament.dosen.pages.unscored-scoring';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access examination/scoring') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'dosen', $tenant);
    }

    public static function examTypeBadgeColor(?string $examTypeName, ?string $examTypeCode = null): string
    {
        return DosenScoringPresenter::examTypeBadgeColor($examTypeName, $examTypeCode);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('all')
                ->label('Arsip Penilaian')
                ->icon('heroicon-o-queue-list')
                ->url(Scoring::getUrl()),
            Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(Dashboard::getUrl()),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return ExamScore::query()
            ->with([
                'lecture:id,name',
                'registration.student',
                'registration.examtype',
                'registration.chief:id,name',
                'registration.examScores' => fn ($query) => $query
                    ->select('id', 'exam_registration_id', 'user_id', 'examiner_order', 'grade')
                    ->with('lecture:id,name'),
            ])
            ->join('exam_registrations', 'exam_registrations.id', '=', 'exam_scores.exam_registration_id')
            ->where('exam_scores.user_id', auth()->id())
            ->whereHas('registration', fn (Builder $query) => $query->whereExaminerScoringIncomplete())
            ->select('exam_scores.*')
            ->orderBy('exam_registrations.exam_date')
            ->orderBy('exam_registrations.exam_time');
    }

    protected function applyGlobalSearchToTableQuery(Builder $query): Builder
    {
        $search = $this->getTableSearch();

        if (blank($search)) {
            return $query;
        }

        foreach ($this->extractTableSearchWords($search) as $searchWord) {
            $query->where(function (Builder $query) use ($searchWord): void {
                $query->whereHas('registration.student', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"))
                    ->orWhereHas('registration.examtype', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"));
            });
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->searchable()
            ->searchPlaceholder('Cari mahasiswa atau jenis ujian...')
            ->columns([
                View::make('filament.dosen.pages.unscored-scoring-card'),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->actionsAlignment('start')
            ->actions([
                Tables\Actions\Action::make('score')
                    ->label('Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (ExamScore $record): string => EditScoring::getUrl(['record' => $record->id])),
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
                    ->url(fn (ExamScore $record): string => ViewChiefExam::getUrl(['record' => $record->exam_registration_id]))
                    ->visible(fn (ExamScore $record): bool => $record->user_id === $record->registration?->chief_id),
            ])
            ->emptyStateHeading('Semua ujian sudah selesai dinilai')
            ->emptyStateDescription('Tidak ada ujian yang masih menunggu penilaian penguji.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->paginated([10, 25, 50]);
    }
}
