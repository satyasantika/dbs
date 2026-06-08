<?php

namespace App\Filament\Dosen\Widgets;

use App\Models\ExamScore;
use App\Models\GuideExaminer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DosenStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $userId = auth()->id();

        $unscoredCount = ExamScore::query()
            ->where('user_id', $userId)
            ->whereNull('grade')
            ->count();

        $supervisedQuery = GuideExaminer::query()
            ->where(function ($query) use ($userId) {
                $query->where('guide1_id', $userId)
                    ->orWhere('guide2_id', $userId);
            });

        $activeSupervisedCount = (clone $supervisedQuery)
            ->whereNull('thesis_date')
            ->count();

        $graduatedSupervisedCount = (clone $supervisedQuery)
            ->whereNotNull('thesis_date')
            ->count();

        $stats = [];

        if (auth()->user()?->can('access examination/scoring')) {
            $stats[] = Stat::make('Belum Dinilai', $unscoredCount)
                ->description('Penilaian ujian yang menunggu input')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unscoredCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(route('scoring.index'));
        }

        $stats[] = Stat::make('Bimbingan Belum Lulus', $activeSupervisedCount)
            ->description('Mahasiswa bimbingan aktif')
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('primary')
            ->icon('heroicon-o-user-group')
            ->url(route('information.guide'));

        $stats[] = Stat::make('Bimbingan Lulus', $graduatedSupervisedCount)
            ->description('Mahasiswa bimbingan sudah sidang')
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->url(route('information.pass'));

        return $stats;
    }
}
