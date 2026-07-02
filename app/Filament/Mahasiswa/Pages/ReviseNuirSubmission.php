<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Filament\Mahasiswa\Concerns\PreparesNuirSubmissionForm;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirSubmissionService;
use Filament\Pages\Page;

class ReviseNuirSubmission extends Page
{
    use AuthorizesMahasiswaPanelAccess;
    use PreparesNuirSubmissionForm;

    protected static ?string $title = 'Revisi Pengajuan NUIR';

    protected static ?string $slug = 'nuir-submission/{record}/revise';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.mahasiswa.pages.manage-nuir-submission';

    public NuirSetting $setting;

    public NuirSubmission $submission;

    public int $stage = 1;

    /** @var array<int, string> */
    public array $rejectedRefs = [];

    public ?NuirSubmission $revisionParent = null;

    public bool $referencesOnly = false;

    public bool $partialNuiOnly = false;

    /** @var list<string> */
    public array $rejectedNuiFields = [];

    public bool $titleSlotOnly = false;

    public function mount(NuirSubmission $record): void
    {
        $this->redirect(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'));
    }

    protected static function mahasiswaAccessPermission(): string
    {
        return 'update nuir submission';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
