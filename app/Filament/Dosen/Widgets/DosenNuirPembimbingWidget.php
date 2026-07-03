<?php

namespace App\Filament\Dosen\Widgets;

use App\Models\NuirProposal;
use App\Support\NuirGuideSeatSync;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class DosenNuirPembimbingWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Review Usulan NUIR';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->can('respond nuir proposal') ?? false;
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        $activeProposals = NuirProposal::where('final', false)
            ->where(fn ($query) => $query->where('guide1_id', $userId)->orWhere('guide2_id', $userId))
            ->with('submission')
            ->get();

        $pendingResponse = $activeProposals->filter(
            fn (NuirProposal $proposal): bool => ($proposal->guide1_id === $userId && $proposal->guide1_status === 'pending')
                || ($proposal->guide2_id === $userId && $proposal->guide2_status === 'pending')
        )->count();

        $needsReview = $this->countNeedingReview($activeProposals, $userId);

        return [
            Stat::make('Usulan Menunggu Respons', $pendingResponse)
                ->description('Terima atau tolak usulan calon pembimbing')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($pendingResponse > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-inbox')
                ->url(route('nuir.dosen.index')),
            Stat::make('Perlu Review Judul/NUI', $needsReview)
                ->description('Setujui atau minta revisi Judul, Novelty, Urgency, Impact')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($needsReview > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-document-magnifying-glass')
                ->url(route('nuir.dosen.index')),
        ];
    }

    /**
     * @param  Collection<int, NuirProposal>  $proposals
     */
    private function countNeedingReview(Collection $proposals, int $userId): int
    {
        $seatSync = app(NuirGuideSeatSync::class);
        $guide = auth()->user();

        return $proposals
            ->filter(function (NuirProposal $proposal) use ($userId): bool {
                $status = $proposal->guide1_id === $userId
                    ? $proposal->guide1_status
                    : ($proposal->guide2_id === $userId ? $proposal->guide2_status : null);

                return in_array($status, ['pending', 'accepted'], true);
            })
            ->filter(fn (NuirProposal $proposal): bool => $proposal->submission?->isContentFinalForPembimbing() ?? false)
            ->reject(fn (NuirProposal $proposal): bool => $seatSync->guideHasApprovedAllNuiFields($proposal, $guide))
            ->count();
    }
}
