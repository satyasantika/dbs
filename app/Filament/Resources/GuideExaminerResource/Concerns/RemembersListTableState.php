<?php

namespace App\Filament\Resources\GuideExaminerResource\Concerns;

trait RemembersListTableState
{
    /**
     * @return array<string, mixed>
     */
    protected function getListTableStateQueryParameters(): array
    {
        return array_filter([
            'tableFilters' => $this->tableFilters ?? null,
            'tableSearch' => filled($this->tableSearch ?? null) ? $this->tableSearch : null,
            'tableSortColumn' => $this->tableSortColumn ?? null,
            'tableSortDirection' => $this->tableSortDirection ?? null,
            'tableGrouping' => $this->tableGrouping ?? null,
            'tableGroupingDirection' => $this->tableGroupingDirection ?? null,
            'activeTab' => $this->activeTab ?? null,
        ], fn ($value) => filled($value));
    }

    protected function appendListTableStateToUrl(string $url): string
    {
        $query = $this->getListTableStateQueryParameters();

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }
}
