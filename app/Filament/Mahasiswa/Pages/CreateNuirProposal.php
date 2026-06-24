<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirProposalService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CreateNuirProposal extends Page
{
    use AuthorizesMahasiswaPanelAccess;

    protected static ?string $title = 'Buat Usulan Calon Pembimbing';

    protected static ?string $slug = 'nuir-proposal/create';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.mahasiswa.pages.create-nuir-proposal';

    public NuirSubmission $submission;

    public bool $previousRejected = false;

    public Collection $lecturers;

    public function mount(NuirProposalService $proposalService): void
    {
        $result = $proposalService->createFormData(auth()->user());

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            $this->redirect($result->getTargetUrl());

            return;
        }

        $this->submission = $result['submission'];
        $this->previousRejected = $result['previousRejected'];
        $this->lecturers = $result['lecturers'];
    }

    protected static function mahasiswaAccessPermission(): string
    {
        return 'create nuir proposal';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
