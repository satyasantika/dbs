<?php

namespace App\Filament\Mahasiswa\Widgets;

use App\Filament\Mahasiswa\Pages\NuirProposalOverview;
use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Models\NuirProposal;
use App\Services\NuirService;
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
        $service = app(NuirService::class);
        $submission = $service->activeSubmission($user);
        $finalProposal = NuirProposal::whereHas('submission', fn ($q) => $q->where('user_id', $user->id))
            ->where('final', true)
            ->exists();

        if (! $submission) {
            return [
                Stat::make('Status NUIR', 'Belum ada')
                    ->description('Buat pengajuan NUIR baru')
                    ->descriptionIcon('heroicon-m-plus-circle')
                    ->color('gray')
                    ->icon('heroicon-o-document-plus')
                    ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            ];
        }

        $approved = $submission->references()->where('ref_approved', true)->count();
        $rejected = $submission->references()->where('ref_approved', false)->count();
        $pending = $submission->references()->whereNull('ref_approved')->count();
        $pendingProposals = NuirProposal::where('nuir_submission_id', $submission->id)
            ->where(function ($query) {
                $query->where('guide1_status', 'pending')
                    ->orWhere('guide2_status', 'pending');
            })
            ->count();

        $statusLabel = match ($submission->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'revision' => 'Revisi',
            'content_ok' => 'Konten OK',
            'finalized' => 'Final',
            default => $submission->status,
        };

        $statusColor = match ($submission->status) {
            'draft' => 'gray',
            'submitted' => 'warning',
            'revision' => 'danger',
            'content_ok' => 'success',
            'finalized' => 'success',
            default => 'primary',
        };

        $stats = [
            Stat::make('Status NUIR', $statusLabel)
                ->description('Versi '.$submission->version)
                ->descriptionIcon('heroicon-m-document-text')
                ->color($statusColor)
                ->icon('heroicon-o-document-text')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            Stat::make('Referensi Disetujui', $approved)
                ->description('Feedback validator')
                ->descriptionIcon('heroicon-m-check')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            Stat::make('Referensi Ditolak', $rejected)
                ->description('Perlu perbaikan')
                ->descriptionIcon('heroicon-m-x-mark')
                ->color($rejected > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-x-circle')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
            Stat::make('Referensi Pending', $pending)
                ->description('Menunggu review validator')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock')
                ->url(NuirSubmissionOverview::getUrl(panel: 'mahasiswa')),
        ];

        if ($finalProposal) {
            $stats[] = Stat::make('Pembimbing', 'Ditetapkan')
                ->description('Usulan calon pembimbing final')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->icon('heroicon-o-user-group')
                ->url(NuirProposalOverview::getUrl(panel: 'mahasiswa'));
        } else {
            $stats[] = Stat::make('Usulan Pembimbing', $pendingProposals)
                ->description('Usulan calon pembimbing pending')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($pendingProposals > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-user-group')
                ->url(NuirProposalOverview::getUrl(panel: 'mahasiswa'));
        }

        return $stats;
    }
}
