<?php

namespace App\Filament\NuirValidator\Widgets;

use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Models\NuirAssignment;
use App\Models\NuirReference;
use App\Models\NuirSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ValidatorNuirStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $validatorId = auth()->id();

        $assignedCount = NuirAssignment::where('validator_id', $validatorId)->count();

        $assignedSubmissionQuery = fn (): Builder => NuirSubmission::query()
            ->whereHas('assignment', fn (Builder $query) => $query->where('validator_id', $validatorId));

        $pendingReferences = NuirReference::query()
            ->whereHas('submission.assignment', fn (Builder $query) => $query->where('validator_id', $validatorId))
            ->whereNull('ref_approved')
            ->count();

        $reviewedReferences = NuirReference::query()
            ->whereHas('submission.assignment', fn (Builder $query) => $query->where('validator_id', $validatorId))
            ->whereNotNull('ref_approved')
            ->count();

        $revisionCount = $assignedSubmissionQuery()
            ->where('status', 'revision')
            ->count();

        $indexUrl = NuirSubmissionResource::getUrl('index', panel: 'nuir-validator');

        return [
            Stat::make('Submission Ditugaskan', $assignedCount)
                ->description('Delegasi dari manajer NUIR')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('primary')
                ->icon('heroicon-o-inbox-stack')
                ->url($indexUrl),
            Stat::make('Referensi Pending', $pendingReferences)
                ->description('Belum disetujui/ditolak')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingReferences > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock')
                ->url($indexUrl),
            Stat::make('Referensi Direview', $reviewedReferences)
                ->description('Sudah disetujui atau ditolak')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->url($indexUrl),
            Stat::make('Permintaan Revisi', $revisionCount)
                ->description('Submission status revision')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($revisionCount > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-arrow-path')
                ->url($indexUrl),
        ];
    }
}
