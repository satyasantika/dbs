<?php

namespace Tests\Unit;

use App\Services\Information\AcademicSemester;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcademicSemesterTest extends TestCase
{
    public function test_gasal_semester_code_for_august_to_december(): void
    {
        $this->assertSame('20251', AcademicSemester::codeFromDate(Carbon::parse('2025-08-01')));
        $this->assertSame('20251', AcademicSemester::codeFromDate(Carbon::parse('2025-12-15')));
    }

    public function test_genap_semester_code_for_january_to_july(): void
    {
        $this->assertSame('20252', AcademicSemester::codeFromDate(Carbon::parse('2026-01-10')));
        $this->assertSame('20252', AcademicSemester::codeFromDate(Carbon::parse('2026-07-31')));
    }

    public function test_semester_label_includes_period_and_academic_year(): void
    {
        $this->assertSame('20251 (Gasal 2025/2026)', AcademicSemester::label('20251'));
        $this->assertSame('20252 (Genap 2025/2026)', AcademicSemester::label('20252'));
    }

    public function test_study_duration_uses_september_of_cohort_year(): void
    {
        $record = new \App\Models\GuideExaminer([
            'year_generation' => '2020',
            'thesis_date' => '2024-03-15',
        ]);

        $this->assertSame('3 tahun 6 bulan', AcademicSemester::studyDuration($record));
    }
}
