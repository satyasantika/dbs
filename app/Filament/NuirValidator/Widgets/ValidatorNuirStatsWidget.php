<?php

namespace App\Filament\NuirValidator\Widgets;

use App\Filament\NuirValidator\Resources\NuirReferenceResource;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Support\NuirValidatorReferenceStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ValidatorNuirStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $assignedSubmissionQuery = NuirSubmissionResource::getEloquentQuery();
        $assignedReferenceQuery = NuirReferenceResource::assignedReferencesQuery();

        $assignedCount = (clone $assignedSubmissionQuery)->count();
        $pendingReferences = $this->filteredReferenceCount(
            $assignedReferenceQuery,
            NuirReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES,
        );
        $validationCompleteCount = $this->filteredSubmissionCount(
            $assignedSubmissionQuery,
            NuirSubmissionResource::DASHBOARD_VIEW_VALIDATION_COMPLETE,
        );
        $awaitingRevalidation = $this->filteredReferenceCount(
            $assignedReferenceQuery,
            NuirReferenceResource::DASHBOARD_VIEW_AWAITING_REVALIDATION,
        );

        return [
            Stat::make('Submission Ditugaskan', $assignedCount)
                ->description('Delegasi dari manajer NUIR')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('primary')
                ->icon('heroicon-o-inbox-stack')
                ->url(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_ASSIGNED)),
            Stat::make('Referensi Pending', $pendingReferences)
                ->description('Belum pernah divalidasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingReferences > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock')
                ->url(NuirReferenceResource::listUrl(NuirReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES)),
            Stat::make('Validasi Selesai', $validationCompleteCount)
                ->description('Semua referensi disetujui')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->url(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_VALIDATION_COMPLETE)),
            Stat::make('Permintaan Revisi', $awaitingRevalidation)
                ->description('Sudah direvisi, menunggu validasi ulang')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($awaitingRevalidation > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-arrow-path')
                ->url(NuirReferenceResource::listUrl(NuirReferenceResource::DASHBOARD_VIEW_AWAITING_REVALIDATION)),
        ];
    }

    protected function filteredReferenceCount(Builder $query, string $view): int
    {
        return NuirReferenceResource::applyDashboardViewFilter(clone $query, $view)->count();
    }

    protected function filteredSubmissionCount(Builder $query, string $view): int
    {
        return NuirSubmissionResource::applyDashboardViewFilter(clone $query, $view)->count();
    }
}
