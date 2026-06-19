<?php

namespace App\Services\Examination;

use App\Models\ExamRegistration;
use App\Models\ExamScore;

class ExamScoreUpdater
{
    public function convertToLetter(float $grade): string
    {
        if ($grade >= 85) {
            return 'A';
        }
        if ($grade >= 77) {
            return 'A-';
        }
        if ($grade >= 69) {
            return 'B+';
        }
        if ($grade >= 61) {
            return 'B';
        }
        if ($grade >= 53) {
            return 'B-';
        }
        if ($grade >= 45) {
            return 'C+';
        }
        if ($grade >= 37) {
            return 'C';
        }
        if ($grade >= 29) {
            return 'C-';
        }
        if ($grade >= 21) {
            return 'D';
        }

        return 'E';
    }

    public function update(ExamScore $scoring, array $data): ExamScore
    {
        $grade = 0;

        for ($i = 1; $i <= 5; $i++) {
            $score = 'score0'.$i;
            $grade += (float) ($data[$score] ?? 0);
        }

        $finalGrade = round($grade / 5, 2);
        $data['exam_registration_id'] = $scoring->exam_registration_id;
        $data['grade'] = $finalGrade;
        $data['letter'] = $this->convertToLetter($finalGrade);

        if ((int) ($data['revision'] ?? 0) === 0) {
            $data['revision_note'] = null;
        }

        $data['scoring_edit_unlocked_at'] = null;

        $scoring->fill($data)->save();

        $this->recalculateExamRegistration($scoring->exam_registration_id);

        return $scoring->fresh();
    }

    public function applyAdminFinalGrade(ExamScore $scoring, float|int $finalGrade): ExamScore
    {
        $finalGrade = (int) max(0, min(100, round($finalGrade)));

        $scoring->fill([
            'score01' => $finalGrade,
            'score02' => $finalGrade,
            'score03' => $finalGrade,
            'score04' => $finalGrade,
            'score05' => $finalGrade,
            'grade' => $finalGrade,
            'letter' => $this->convertToLetter((float) $finalGrade),
            'pass_approved' => $finalGrade >= 37 ? 1 : 0,
        ])->save();

        $this->recalculateExamRegistration($scoring->exam_registration_id);

        return $scoring->fresh();
    }

    protected function recalculateExamRegistration(int $examRegistrationId): void
    {
        $examRegistration = ExamRegistration::find($examRegistrationId);

        if (! $examRegistration) {
            return;
        }

        $gradeSum = ExamScore::where('exam_registration_id', $examRegistrationId)->sum('grade');
        $passApprovedSum = ExamScore::where('exam_registration_id', $examRegistrationId)->sum('pass_approved');
        $registrationGrade = round($gradeSum / 5, 2);

        $examRegistration->grade = $registrationGrade;
        $examRegistration->letter = $this->convertToLetter($registrationGrade);

        if ($passApprovedSum == 5) {
            $examRegistration->pass_exam = 1;
        }

        $examRegistration->save();
    }
}
