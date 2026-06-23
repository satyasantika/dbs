<?php

namespace App\Filament\Resources\GuideExaminerResource\Concerns;

use Livewire\Attributes\Url;

trait HasListTableStateUrl
{
    #[Url]
    public ?array $tableFilters = null;

    #[Url]
    public ?string $tableSearch = '';

    #[Url]
    public ?string $tableSortColumn = null;

    #[Url]
    public ?string $tableSortDirection = null;

    #[Url]
    public ?string $tableGrouping = null;

    #[Url]
    public ?string $tableGroupingDirection = null;

    #[Url]
    public ?string $activeTab = null;
}
