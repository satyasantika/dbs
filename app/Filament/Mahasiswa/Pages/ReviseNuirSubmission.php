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

    public function mount(NuirSubmission $record): void
    {
        $data = app(NuirSubmissionService::class)->revisionFormData(auth()->user(), $record);
        $this->setting = $data['setting'];
        $this->submission = $data['submission'];
        $this->stage = $data['stage'];
        $this->rejectedRefs = $data['rejectedRefs'];
        $this->revisionParent = $data['revisionParent'];
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
