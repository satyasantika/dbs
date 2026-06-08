<?php

namespace App\Filament\Dosen\Widgets;

use App\Filament\Dosen\Pages\Scoring;
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
            ->where('exam_scores.user_id', $userId)
            ->whereNull('exam_scores.grade')
            ->count();

        $totalScoringCount = ExamScore::query()
            ->where('exam_scores.user_id', $userId)
            ->count();

        $supervisedQuery = GuideExaminer::query()
            ->where(function ($query) use ($userId) {
                $query->where('guide1_id', $userId)
                    ->orWhere('guide2_id', $userId);
            });

        $activeSupervisedCount = (clone $supervisedQuery)
            ->whereNull('thesis_date')
            ->count();

        $graduatedCount = GuideExaminer::query()
            ->where(function ($query) use ($userId) {
                $query->where('guide1_id', $userId)
                    ->orWhere('guide2_id', $userId)
                    ->orWhere('examiner1_id', $userId)
                    ->orWhere('examiner2_id', $userId)
                    ->orWhere('examiner3_id', $userId);
            })
            ->whereNotNull('thesis_date')
            ->count();

        $stats = [];

        if (auth()->user()?->can('access examination/scoring')) {
            $stats[] = Stat::make('Belum Dinilai', $unscoredCount)
                ->description('Penilaian ujian yang menunggu input')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unscoredCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(Scoring::getUrl(['activeTab' => 'unscored']));

            $stats[] = Stat::make('Penilaian Keseluruhan', $totalScoringCount)
                ->description('Semua penugasan penilaian ujian')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->icon('heroicon-o-queue-list')
                ->url(Scoring::getUrl(['activeTab' => 'all']));
        }

        $stats[] = Stat::make('Bimbingan Belum Lulus', $activeSupervisedCount)
            ->description('Mahasiswa bimbingan aktif')
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('primary')
            ->icon('heroicon-o-user-group')
            ->url(route('information.guide'));

        $stats[] = Stat::make('Bimbingan/Penguji yang sudah lulus', $graduatedCount)
            ->description('Bukti membimbing / menguji untuk BKD')
            ->descriptionIcon('heroicon-m-document-check')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->url(route('information.pass'));

        return $stats;
    }
}
