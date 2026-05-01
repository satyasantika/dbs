<?php

namespace App\Filament\Widgets;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExamStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalExams = ExamRegistration::count();

        $unscoredExamIds = ExamScore::whereNull('pass_approved')
            ->distinct('exam_registration_id')
            ->pluck('exam_registration_id');
        $unscoredCount = $unscoredExamIds->unique()->count();

        $setExamIds = ExamScore::select('exam_registration_id')
            ->groupBy('exam_registration_id')
            ->pluck('exam_registration_id');
        $notSetCount = ExamRegistration::whereNotIn('id', $setExamIds)->count();

        return [
            Stat::make('Total Ujian Terdaftar', $totalExams)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make('Belum Dinilai', $unscoredCount)
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(route('get.examinerscoringyet')),
            Stat::make('Belum Diset ke Penguji', $notSetCount)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(route('get.setscoringtoexamineryet')),
        ];
    }
}
