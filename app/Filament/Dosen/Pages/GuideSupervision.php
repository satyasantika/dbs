<?php

namespace App\Filament\Dosen\Pages;

use App\Filament\Dosen\Concerns\HasGuideSupervisionRecap;
use App\Models\GuideExaminer;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuideSupervision extends Page implements HasTable
{
    use HasGuideSupervisionRecap;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $title = 'Bimbingan Belum Lulus';

    protected static ?string $slug = 'information/guides';

    protected static string $view = 'filament.dosen.pages.guide-supervision';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('active') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'dosen', $tenant);
    }

    public static function progressLabel(GuideExaminer $record): string
    {
        return match (true) {
            is_null($record->proposal_date) && is_null($record->seminar_date) => 'Belum Sempro',
            filled($record->proposal_date) && is_null($record->seminar_date) => 'Baru Sempro',
            filled($record->seminar_date) => 'Sudah Semhas',
            default => '—',
        };
    }

    public static function progressBadgeColor(GuideExaminer $record): string
    {
        return match (self::progressLabel($record)) {
            'Belum Sempro' => 'gray',
            'Baru Sempro' => 'info',
            'Sudah Semhas' => 'primary',
            default => 'gray',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pass')
                ->label('Bimbingan/Penguji Lulus')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->url(GraduationEvidence::getUrl()),
            Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(Dashboard::getUrl()),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return GuideExaminer::query()
            ->with([
                'student:id,name,username,phone',
                'guide1:id,name',
                'guide2:id,name',
            ])
            ->join('users as supervised_students', 'guide_examiners.user_id', '=', 'supervised_students.id')
            ->select('guide_examiners.*')
            ->where(function (Builder $query): void {
                $query->where('guide_examiners.guide1_id', auth()->id())
                    ->orWhere('guide_examiners.guide2_id', auth()->id());
            })
            ->whereNull('guide_examiners.thesis_date')
            ->orderByDesc('guide_examiners.year_generation')
            ->orderBy('supervised_students.name');
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
                    ->orWhereHas('guide2', fn (Builder $q) => $q->where('name', 'like', "%{$searchWord}%"));
            });
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->defaultSort('year_generation', 'desc')
            ->searchable()
            ->searchPlaceholder('Cari mahasiswa, NPM, angkatan, atau pembimbing...')
            ->columns([
                View::make('filament.dosen.pages.guide-supervision-card'),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->actionsAlignment('start')
            ->actions([
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
            ->emptyStateHeading('Belum ada data bimbingan')
            ->emptyStateDescription('Mahasiswa bimbingan aktif akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->paginated([10, 25, 50]);
    }
}
