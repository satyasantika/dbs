<?php

namespace App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
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
        if (blank($this->dashboardView)) {
            return 'Semua submission non-draft versi terbaru.';
        }

        return match ($this->dashboardView) {
            NuirSubmissionResource::DASHBOARD_VIEW_UNASSIGNED => 'Submission aktif yang belum ditugaskan ke validator.',
            NuirSubmissionResource::DASHBOARD_VIEW_SUBMITTED => 'Submission dengan status submitted, menunggu proses review.',
            NuirSubmissionResource::DASHBOARD_VIEW_REVISION => 'Submission yang diminta revisi dan menunggu perbaikan mahasiswa.',
            NuirSubmissionResource::DASHBOARD_VIEW_CONTENT_OK => 'Submission dengan konten disetujui, siap usulan calon pembimbing.',
            default => null,
        };
    }

    protected function getTableQuery(): ?Builder
    {
        return NuirSubmissionResource::applyDashboardViewFilter(
            parent::getTableQuery(),
            $this->dashboardView,
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
