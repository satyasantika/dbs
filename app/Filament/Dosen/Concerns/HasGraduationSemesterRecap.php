<?php

namespace App\Filament\Dosen\Concerns;

use App\Models\GuideExaminer;
use App\Services\Information\AcademicSemester;
use Illuminate\Database\Eloquent\Builder;

trait HasGraduationSemesterRecap
{
    protected function getGraduatedRecordsQuery(): Builder
    {
        $userId = auth()->id();

        return GuideExaminer::query()
            ->whereNotNull('thesis_date')
            ->where(function (Builder $query) use ($userId): void {
                $query->where('guide1_id', $userId)
                    ->orWhere('guide2_id', $userId)
                    ->orWhere('examiner1_id', $userId)
                    ->orWhere('examiner2_id', $userId)
                    ->orWhere('examiner3_id', $userId);
            });
    }

    /**
     * @return array<int, array{
     *     code: string,
     *     label: string,
     *     pembimbing: int,
     *     penguji: int
     * }>
     */
    public function getGraduationRecapBySemester(): array
    {
        $userId = auth()->id();

        $records = $this->getGraduatedRecordsQuery()
            ->get([
                'id',
                'guide1_id',
                'guide2_id',
                'examiner1_id',
                'examiner2_id',
                'examiner3_id',
                'thesis_date',
            ]);

        $recap = [];

        foreach ($records as $record) {
            $code = AcademicSemester::codeFromDate($record->thesis_date);

            if (! isset($recap[$code])) {
                $recap[$code] = [
                    'code' => $code,
                    'label' => AcademicSemester::label($code),
                    'pembimbing' => 0,
                    'penguji' => 0,
                ];
            }

            match (AcademicSemester::userRole($record, $userId)) {
                'pembimbing' => $recap[$code]['pembimbing']++,
                'penguji' => $recap[$code]['penguji']++,
                default => null,
            };
        }

        krsort($recap);

        return array_values($recap);
    }

    /**
     * @return array<string, string>
     */
    public function getSemesterFilterOptions(): array
    {
        return collect($this->getGraduationRecapBySemester())
            ->mapWithKeys(fn (array $row): array => [$row['code'] => $row['label']])
            ->all();
    }
}
