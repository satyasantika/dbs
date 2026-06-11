<?php

namespace App\Services\Information;

use App\Models\GuideExaminer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AcademicSemester
{
    public static function codeFromDate(Carbon|string $date): string
    {
        $date = Carbon::parse($date);
        $month = $date->month;
        $year = $date->year;

        if ($month >= 8) {
            $academicStartYear = $year;
            $semester = 1;
        } else {
            $academicStartYear = $year - 1;
            $semester = 2;
        }

        return $academicStartYear.$semester;
    }

    public static function label(string $code): string
    {
        $startYear = (int) substr($code, 0, -1);
        $semester = (int) substr($code, -1);
        $period = $semester === 1 ? 'Gasal' : 'Genap';

        return sprintf('%s (%s %d/%d)', $code, $period, $startYear, $startYear + 1);
    }

    public static function applySemesterFilter(Builder $query, string $code, string $column = 'thesis_date'): Builder
    {
        $startYear = (int) substr($code, 0, -1);
        $semester = (int) substr($code, -1);

        if ($semester === 1) {
            return $query
                ->whereYear($column, $startYear)
                ->whereMonth($column, '>=', 8);
        }

        return $query
            ->whereYear($column, $startYear + 1)
            ->whereMonth($column, '<=', 7);
    }

    public static function applyUserRoleFilter(
        Builder $query,
        int $userId,
        string $role,
        string $prefix = 'guide_examiners',
    ): Builder {
        return match ($role) {
            'pembimbing' => $query->where(function (Builder $query) use ($userId, $prefix): void {
                $query->where("{$prefix}.guide1_id", $userId)
                    ->orWhere("{$prefix}.guide2_id", $userId);
            }),
            'penguji' => $query->where(function (Builder $query) use ($userId, $prefix): void {
                $query->where("{$prefix}.examiner1_id", $userId)
                    ->orWhere("{$prefix}.examiner2_id", $userId)
                    ->orWhere("{$prefix}.examiner3_id", $userId);
            }),
            default => $query,
        };
    }

    public static function userRole(GuideExaminer $record, int $userId): ?string
    {
        if ((int) $record->guide1_id === $userId || (int) $record->guide2_id === $userId) {
            return 'pembimbing';
        }

        if (in_array($userId, [
            (int) $record->examiner1_id,
            (int) $record->examiner2_id,
            (int) $record->examiner3_id,
        ], true)) {
            return 'penguji';
        }

        return null;
    }

    public static function roleLabel(GuideExaminer $record, int $userId): string
    {
        return match (self::userRole($record, $userId)) {
            'pembimbing' => 'Pembimbing',
            'penguji' => 'Penguji',
            default => '—',
        };
    }

    public static function roleBadgeColor(GuideExaminer $record, int $userId): string
    {
        return match (self::userRole($record, $userId)) {
            'pembimbing' => 'primary',
            'penguji' => 'success',
            default => 'gray',
        };
    }

    public static function studyDuration(GuideExaminer $record): ?string
    {
        if (blank($record->thesis_date) || blank($record->year_generation)) {
            return null;
        }

        $entryDate = Carbon::createFromDate((int) $record->year_generation, 9, 1)->startOfDay();
        $duration = $entryDate->diff($record->thesis_date);

        return $duration->y.' tahun '.$duration->m.' bulan';
    }
}
