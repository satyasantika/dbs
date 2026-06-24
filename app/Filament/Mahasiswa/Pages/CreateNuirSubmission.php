<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Filament\Mahasiswa\Concerns\PreparesNuirSubmissionForm;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirSubmissionService;
use Filament\Pages\Page;

class CreateNuirSubmission extends Page
{
    use AuthorizesMahasiswaPanelAccess;
    use PreparesNuirSubmissionForm;

    protected static ?string $title = 'Buat Pengajuan NUIR';

    protected static ?string $slug = 'nuir-submission/create';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.mahasiswa.pages.manage-nuir-submission';

    public NuirSetting $setting;

    public NuirSubmission $submission;

    public int $stage = 1;

    /** @var array<int, string> */
    public array $rejectedRefs = [];

    public ?NuirSubmission $revisionParent = null;

    public function mount(NuirSubmissionService $submissionService): void
    {
        $result = $submissionService->createFormData(auth()->user());

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            $this->redirect($result->getTargetUrl());

            return;
        }

        $this->setting = $result['setting'];
        $this->submission = $result['submission'];
        $this->stage = $result['stage'];
        $this->rejectedRefs = $result['rejectedRefs'];
        $this->revisionParent = $result['revisionParent'];
    }

    protected static function mahasiswaAccessPermission(): string
    {
        return 'create nuir submission';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
