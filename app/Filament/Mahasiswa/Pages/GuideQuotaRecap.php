<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Models\GuideAllocation;
use App\Models\GuideExaminer;
use App\Services\NuirGuideQuotaService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuideQuotaRecap extends Page implements HasTable
{
    use AuthorizesMahasiswaPanelAccess;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'NUIR';

    protected static ?string $navigationLabel = 'Rekap Kuota Pembimbing';

    protected static ?string $title = 'Rekap Kuota Pembimbing';

    protected static ?string $slug = 'guide-quota-recap';

    protected static string $view = 'filament.mahasiswa.pages.guide-quota-recap';

    public ?string $yearGeneration = null;

    protected static function mahasiswaAccessPermission(): string
    {
        return 'active';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }

    public function mount(): void
    {
        $this->yearGeneration = GuideExaminer::where('user_id', auth()->id())->value('year_generation');
    }

    protected function getTableQuery(): Builder
    {
        return GuideAllocation::query()
            ->with('lecture')
            ->where('active', true)
            ->where('year', (int) $this->yearGeneration);
    }

    public function table(Table $table): Table
    {
        $quotaService = app(NuirGuideQuotaService::class);

        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('lecture.name')->label('Dosen')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('guide1_quota')->label('Kuota P1'),
                Tables\Columns\TextColumn::make('guide1_filled')->label('Terisi P1'),
                Tables\Columns\TextColumn::make('sisa_p1')
                    ->label('Sisa P1')
                    ->state(fn (GuideAllocation $record) => $quotaService->remainingQuota($record->lecture, 1, (string) $this->yearGeneration))
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('guide2_quota')->label('Kuota P2'),
                Tables\Columns\TextColumn::make('guide2_filled')->label('Terisi P2'),
                Tables\Columns\TextColumn::make('sisa_p2')
                    ->label('Sisa P2')
                    ->state(fn (GuideAllocation $record) => $quotaService->remainingQuota($record->lecture, 2, (string) $this->yearGeneration))
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->defaultSort('lecture.name')
            ->emptyStateHeading('Belum ada kuota pembimbing untuk angkatan Anda');
    }
}
