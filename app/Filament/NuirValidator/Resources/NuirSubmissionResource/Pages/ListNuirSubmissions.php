<?php

namespace App\Filament\NuirValidator\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListNuirSubmissions extends ListRecords
{
    protected static string $resource = NuirSubmissionResource::class;

    #[Url(as: 'view')]
    public ?string $dashboardView = null;

    public function getTitle(): string
    {
        return NuirSubmissionResource::dashboardViewLabel($this->dashboardView)
            ?? static::getResource()::getPluralModelLabel();
    }

    public function getSubheading(): ?string
    {
        return match ($this->dashboardView) {
            NuirSubmissionResource::DASHBOARD_VIEW_VALIDATION_COMPLETE => 'Submission dengan seluruh referensi sudah disetujui.',
            default => 'Submission NUIR yang didelegasikan manajer ke Anda, beserta ringkasan progress validasi referensi.',
        };
    }

    protected function getTableQuery(): ?Builder
    {
        return NuirSubmissionResource::applyDashboardViewFilter(
            parent::getTableQuery(),
            $this->dashboardView ?? NuirSubmissionResource::DASHBOARD_VIEW_ASSIGNED,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(route('home')),
        ];
    }
}
