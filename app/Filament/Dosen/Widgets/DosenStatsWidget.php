<?php

namespace App\Filament\Dosen\Widgets;

use App\Filament\Dosen\Pages\UnscoredScoring;
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

        $unfinishedCount = ExamScore::query()
            ->where('exam_scores.user_id', $userId)
            ->whereHas('registration', fn ($query) => $query->whereExaminerScoringIncomplete())
            ->count();

        $archivedScoringCount = ExamScore::query()
            ->where('exam_scores.user_id', $userId)
            ->whereHas('registration', fn ($query) => $query->whereExaminerScoringComplete())
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
            $stats[] = Stat::make('Ujian Belum Selesai Dinilai', $unfinishedCount)
                ->description('Ujian yang masih ada penguji belum menilai')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unfinishedCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(UnscoredScoring::getUrl());

            $stats[] = Stat::make('Arsip Penilaian', $archivedScoringCount)
                ->description('Ujian yang sudah dinilai semua penguji')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->icon('heroicon-o-queue-list')
                ->url(Scoring::getUrl());
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
