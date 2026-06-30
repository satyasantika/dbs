<?php

namespace App\Filament\NuirManajer\Widgets;

use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ManajerNuirStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $query = NuirSubmissionResource::activeSubmissionsQuery();

        $total = (clone $query)->count();
        $unassigned = $this->filteredCount($query, NuirSubmissionResource::DASHBOARD_VIEW_UNASSIGNED);
        $submitted = $this->filteredCount($query, NuirSubmissionResource::DASHBOARD_VIEW_SUBMITTED);
        $revision = $this->filteredCount($query, NuirSubmissionResource::DASHBOARD_VIEW_REVISION);
        $contentOk = $this->filteredCount($query, NuirSubmissionResource::DASHBOARD_VIEW_CONTENT_OK);

        return [
            Stat::make('Submission Aktif', $total)
                ->description('Semua submission non-draft')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->url(NuirSubmissionResource::listUrl()),
            Stat::make('Belum Didelegasikan', $unassigned)
                ->description('Perlu ditugaskan ke validator')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($unassigned > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-user-plus')
                ->url(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_UNASSIGNED)),
            Stat::make('Menunggu Review', $submitted)
                ->description('Status submitted')
                ->descriptionIcon('heroicon-m-clock')
                ->color($submitted > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock')
                ->url(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_SUBMITTED)),
            Stat::make('Diminta Revisi', $revision)
                ->description('Menunggu perbaikan mahasiswa')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($revision > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-arrow-path')
                ->url(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_REVISION)),
            Stat::make('Konten Disetujui', $contentOk)
                ->description('Siap usulan calon pembimbing')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->url(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_CONTENT_OK)),
        ];
    }

    protected function filteredCount(Builder $query, string $view): int
    {
        return NuirSubmissionResource::applyDashboardViewFilter(clone $query, $view)->count();
    }
}
