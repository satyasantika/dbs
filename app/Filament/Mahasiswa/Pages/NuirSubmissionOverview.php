<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Filament\Mahasiswa\Concerns\HidesNuirNavigationWhenInactive;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirSubmissionService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class NuirSubmissionOverview extends Page
{
    use AuthorizesMahasiswaPanelAccess;
    use HidesNuirNavigationWhenInactive;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pengajuan NUIR';

    protected static ?string $title = 'Pengajuan NUIR';

    protected static ?string $navigationGroup = 'NUIR';

    protected static ?string $slug = 'nuir-submission';

    protected static string $view = 'filament.mahasiswa.pages.nuir-submission-overview';

    public ?NuirSetting $setting = null;

    public ?NuirSubmission $submission = null;

    public Collection $versions;

    public Collection $revisionHistory;

    public bool $closed = false;

    public bool $stage3 = false;

    public function mount(NuirSubmissionService $submissionService): void
    {
        $data = $submissionService->getIndexData(auth()->user());
        $this->setting = $data['setting'];
        $this->submission = $data['submission'];
        $this->versions = $data['versions'];
        $this->revisionHistory = $data['revisionHistory'];
        $this->closed = $data['closed'];
        $this->stage3 = $data['stage3'];
    }

    protected static function mahasiswaAccessPermission(): string
    {
        return 'access nuir/submission';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
