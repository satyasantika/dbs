<?php

namespace App\Filament\NuirManajer\Widgets;

use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
use App\Models\NuirSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManajerNuirStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $query = NuirSubmission::query()->where('status', '!=', 'draft');

        $total = (clone $query)->count();
        $unassigned = (clone $query)->whereDoesntHave('assignment')->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $revision = (clone $query)->where('status', 'revision')->count();
        $contentOk = (clone $query)->where('status', 'content_ok')->count();

        return [
            Stat::make('Submission Aktif', $total)
                ->description('Semua submission non-draft')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->url(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer')),
            Stat::make('Belum Didelegasikan', $unassigned)
                ->description('Perlu ditugaskan ke validator')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($unassigned > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-user-plus')
                ->url(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer')),
            Stat::make('Menunggu Review', $submitted)
                ->description('Status submitted')
                ->descriptionIcon('heroicon-m-clock')
                ->color($submitted > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock')
                ->url(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer')),
            Stat::make('Diminta Revisi', $revision)
                ->description('Menunggu perbaikan mahasiswa')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($revision > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-arrow-path')
                ->url(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer')),
            Stat::make('Konten Disetujui', $contentOk)
                ->description('Siap usulan calon pembimbing')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->url(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer')),
        ];
    }
}
