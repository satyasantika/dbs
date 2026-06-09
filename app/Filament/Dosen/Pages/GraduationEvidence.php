<?php

namespace App\Filament\Dosen\Pages;

use App\Filament\Dosen\Concerns\HasGraduationSemesterRecap;
use App\Models\GuideExaminer;
use App\Services\Information\AcademicSemester;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GraduationEvidence extends Page implements HasTable
{
    use HasGraduationSemesterRecap;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $title = 'Lulusan Pembimbing Penguji';

    protected static ?string $slug = 'information/pass';

    protected static string $view = 'filament.dosen.pages.graduation-evidence';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('active') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'dosen', $tenant);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guides')
                ->label('Bimbingan Belum Lulus')
                ->icon('heroicon-o-user-group')
                ->color('primary')
                ->url(GuideSupervision::getUrl()),
            Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(Dashboard::getUrl()),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return $this->getGraduatedRecordsQuery()
            ->with([
                'student:id,name,username,phone',
            ])
            ->join('users as graduated_students', 'guide_examiners.user_id', '=', 'graduated_students.id')
            ->select('guide_examiners.*')
            ->orderByDesc('guide_examiners.thesis_date')
            ->orderBy('graduated_students.name');
    }

    protected function applyGlobalSearchToTableQuery(Builder $query): Builder
    {
        $search = $this->getTableSearch();

        if (blank($search)) {
            return $query;
        }

        foreach ($this->extractTableSearchWords($search) as $searchWord) {
            $query->where(function (Builder $query) use ($searchWord): void {
                $query->where('guide_examiners.year_generation', 'like', "%{$searchWord}%")
                    ->orWhereHas('student', fn (Builder $q) => $q
                        ->where('name', 'like', "%{$searchWord}%")
                        ->orWhere('username', 'like', "%{$searchWord}%"))
                    ->orWhereHas('guide1', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"))
                    ->orWhereHas('guide2', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"))
                    ->orWhereHas('examiner1', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"))
                    ->orWhereHas('examiner2', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"))
                    ->orWhereHas('examiner3', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"));
            });
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->defaultSort('thesis_date', 'desc')
            ->searchable()
            ->searchPlaceholder('Cari mahasiswa, NPM, angkatan, pembimbing, atau penguji...')
            ->filters([
                SelectFilter::make('semester')
                    ->label('Semester')
                    ->options(fn (): array => $this->getSemesterFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        return AcademicSemester::applySemesterFilter(
                            $query,
                            (string) $data['value'],
                            'guide_examiners.thesis_date',
                        );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->columns([
                View::make('filament.dosen.pages.graduation-evidence-card'),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->actionsAlignment('start')
            ->actions([
                Tables\Actions\Action::make('doc')
                    ->label('Bukti')
                    ->icon('heroicon-o-document')
                    ->color('primary')
                    ->url(fn (GuideExaminer $record): ?string => filled($record->doc) ? $record->doc : null)
                    ->openUrlInNewTab()
                    ->visible(fn (GuideExaminer $record): bool => filled($record->doc)),
                Tables\Actions\Action::make('whatsapp')
                    ->label('WA')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (GuideExaminer $record): ?string {
                        $phone = $record->student?->phone;

                        if (blank($phone)) {
                            return null;
                        }

                        return 'https://api.whatsapp.com/send/?phone=62'.$phone;
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (GuideExaminer $record): bool => filled($record->student?->phone)),
            ])
            ->emptyStateHeading('Belum ada data kelulusan')
            ->emptyStateDescription('Mahasiswa yang sudah sidang skripsi akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-document-check')
            ->paginated([10, 25, 50]);
    }
}
