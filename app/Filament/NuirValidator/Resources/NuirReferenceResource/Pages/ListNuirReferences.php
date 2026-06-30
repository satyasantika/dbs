<?php

namespace App\Filament\NuirValidator\Resources\NuirReferenceResource\Pages;

use App\Filament\NuirValidator\Pages\Dashboard;
use App\Filament\NuirValidator\Resources\NuirReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListNuirReferences extends ListRecords
{
    protected static string $resource = NuirReferenceResource::class;

    #[Url(as: 'view')]
    public ?string $dashboardView = null;

    public function getTitle(): string
    {
        return NuirReferenceResource::dashboardViewLabel($this->dashboardView)
            ?? static::getResource()::getPluralModelLabel();
    }

    public function getSubheading(): ?string
    {
        if (blank($this->dashboardView)) {
            return 'Daftar referensi yang didelegasikan ke Anda.';
        }

        return match ($this->dashboardView) {
            NuirReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES => 'Referensi yang belum pernah divalidasi.',
            NuirReferenceResource::DASHBOARD_VIEW_AWAITING_REVALIDATION => 'Referensi yang sudah direvisi mahasiswa dan menunggu validasi ulang.',
            default => null,
        };
    }

    protected function getTableQuery(): ?Builder
    {
        return NuirReferenceResource::applyDashboardViewFilter(
            parent::getTableQuery(),
            $this->dashboardView,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label(filled($this->dashboardView) ? 'Kembali ke Dashboard' : 'Dashboard')
                ->icon('heroicon-o-home')
                ->url(Dashboard::getUrl(panel: 'nuir-validator')),
        ];
    }
}
