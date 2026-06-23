<?php

namespace Tests\Unit;

use App\Filament\Resources\GuideExaminerResource;
use Tests\TestCase;

class GuideExaminerScheduleFilterTest extends TestCase
{
    public function test_normalize_exam_schedule_filter_value_casts_semester_code_to_string(): void
    {
        $this->assertSame('20251', GuideExaminerResource::normalizeExamScheduleFilterValue(20251));
        $this->assertSame('unscheduled', GuideExaminerResource::normalizeExamScheduleFilterValue('unscheduled'));
        $this->assertNull(GuideExaminerResource::normalizeExamScheduleFilterValue(null));
        $this->assertNull(GuideExaminerResource::normalizeExamScheduleFilterValue(''));
    }
}
