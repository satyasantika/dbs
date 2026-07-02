<?php

namespace App\Filament\Mahasiswa\Widgets;

use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Services\NuirService;
use App\Support\NuirValidatorReferenceStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MahasiswaNuirStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $setting = app(NuirService::class)->getActiveSetting(auth()->user());

        return $setting
            && $setting->active
            && in_array($setting->stage, [1, 2], true);
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $submission = app(NuirService::class)->activeSubmission($user);

        if (! $submission) {
            return [
                Stat::make('Pengajuan NUIR', 'Belum ada')
                    ->description('Mulai usulan NUIR baru')
                    ->descriptionIcon('heroicon-m-plus-circle')
                    ->color('gray')
                    ->icon('heroicon-o-document-plus')
                    ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            ];
        }

        $submission->load('references');
        $counts = NuirValidatorReferenceStatus::referenceCounts($submission);

        return [
            Stat::make('Pengajuan NUIR', 'Aktif')
                ->description('Versi '.$submission->version)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            Stat::make('Referensi Disetujui', $counts['approved'])
                ->description('Interaksi validator (R)')
                ->descriptionIcon('heroicon-m-check')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            Stat::make('Referensi Revisi', $counts['needs_revision'])
                ->description('Perlu perbaikan')
                ->descriptionIcon('heroicon-m-x-mark')
                ->color($counts['needs_revision'] > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-x-circle')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            Stat::make('Referensi Pending', $counts['pending'] + $counts['awaiting_revalidation'])
                ->description('Menunggu validator')
                ->descriptionIcon('heroicon-m-clock')
                ->color(($counts['pending'] + $counts['awaiting_revalidation']) > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
        ];
    }
}
