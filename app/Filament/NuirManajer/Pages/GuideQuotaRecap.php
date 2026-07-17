<?php

namespace App\Filament\NuirManajer\Pages;

use App\Models\GuideAllocation;
use App\Models\NuirSetting;
use App\Services\NuirGuideQuotaService;
use App\Services\NuirService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class GuideQuotaRecap extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Manajemen NUIR';

    protected static ?string $navigationLabel = 'Rekap Kuota Pembimbing';

    protected static ?string $title = 'Rekap Kuota Pembimbing';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.nuir-manajer.pages.guide-quota-recap';

    #[Url]
    public ?string $yearGeneration = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage nuir guide quota') ?? false;
    }

    public function mount(): void
    {
        $this->yearGeneration ??= NuirSetting::active()->value('year_generation')
            ?? NuirSetting::query()->latest('year_generation')->value('year_generation');
    }

    public function yearOptions(): array
    {
        return NuirSetting::query()
            ->orderByDesc('year_generation')
            ->pluck('year_generation', 'year_generation')
            ->all();
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
            ->poll('15s')
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
            ->emptyStateHeading('Belum ada kuota pembimbing untuk angkatan ini');
    }

    public function canRatify(): bool
    {
        if (blank($this->yearGeneration)) {
            return false;
        }

        return app(NuirService::class)->canRatifySelectionStages($this->yearGeneration);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncQuota')
                ->label('Sinkronisasi Kuota')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Sisa Kuota Pembimbing?')
                ->modalDescription('Menghitung ulang jumlah terisi P1/P2 berdasarkan usulan yang benar-benar masih pending/accepted untuk angkatan ini. Gunakan bila ada indikasi sisa kuota tidak sesuai akibat kegagalan sistem saat pengusulan.')
                ->modalSubmitActionLabel('Sinkronkan')
                ->visible(fn (): bool => (auth()->user()?->can('manage nuir guide quota') ?? false) && filled($this->yearGeneration))
                ->action(function (): void {
                    $corrected = app(NuirGuideQuotaService::class)->reconcile($this->yearGeneration);

                    Notification::make()
                        ->success()
                        ->title($corrected > 0
                            ? "{$corrected} data kuota dosen disinkronkan."
                            : 'Semua data kuota sudah sesuai, tidak ada yang dikoreksi.')
                        ->send();
                }),
            Action::make('ratify')
                ->label('Pengesahan')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Sahkan Pembimbing Angkatan Ini?')
                ->modalDescription('Seluruh pasangan pembimbing yang sudah ditetapkan untuk angkatan ini akan didaftarkan resmi ke data pembimbing mahasiswa.')
                ->visible(fn (): bool => (auth()->user()?->can('ratify selection stage') ?? false) && $this->canRatify())
                ->action(function (): void {
                    $count = app(NuirService::class)->ratifySelectionStages($this->yearGeneration);

                    Notification::make()
                        ->success()
                        ->title("Pembimbing {$count} mahasiswa berhasil disahkan.")
                        ->send();
                }),
        ];
    }
}
