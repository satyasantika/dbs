<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Filament\Mahasiswa\Concerns\HidesNuirNavigationWhenInactive;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Services\NuirProposalService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class NuirProposalOverview extends Page
{
    use AuthorizesMahasiswaPanelAccess;
    use HidesNuirNavigationWhenInactive;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Usulan Calon Pembimbing';

    protected static ?string $title = 'Usulan Calon Pembimbing NUIR';

    protected static ?string $navigationGroup = 'NUIR';

    protected static ?string $slug = 'nuir-proposal';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.mahasiswa.pages.nuir-proposal-overview';

    public Collection $proposals;

    public ?NuirProposal $finalProposal = null;

    public ?NuirSubmission $proposableSubmission = null;

    public function mount(NuirProposalService $proposalService): void
    {
        $data = $proposalService->getIndexData(auth()->user());
        $this->proposals = $data['proposals'];
        $this->finalProposal = $data['finalProposal'];
        $this->proposableSubmission = $data['proposableSubmission'];
    }

    protected static function mahasiswaAccessPermission(): string
    {
        return 'read nuir proposal';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
