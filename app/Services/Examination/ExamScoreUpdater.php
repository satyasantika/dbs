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

        $examRegistration = ExamRegistration::find($scoring->exam_registration_id);
        $gradeSum = ExamScore::where('exam_registration_id', $scoring->exam_registration_id)->sum('grade');
        $passApprovedSum = ExamScore::where('exam_registration_id', $scoring->exam_registration_id)->sum('pass_approved');
        $registrationGrade = round($gradeSum / 5, 2);

        $examRegistration->grade = $registrationGrade;
        $examRegistration->letter = $this->convertToLetter($registrationGrade);

        if ($passApprovedSum == 5) {
            $examRegistration->pass_exam = 1;
        }

        $examRegistration->save();

        return $scoring->fresh();
    }
}
