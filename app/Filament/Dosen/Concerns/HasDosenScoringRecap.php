<?php

namespace App\Filament\Dosen\Concerns;

use App\Services\Examination\DosenScoringPresenter;

trait HasDosenScoringRecap
{
    /**
     * @return array<int, array{name: string, code: string|null, count: int, color: string}>
     */
    public function getExamTypeRecap(): array
    {
        return DosenScoringPresenter::examTypeRecapFromQuery($this->getFilteredTableQuery());
    }
}
