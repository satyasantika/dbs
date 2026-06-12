<?php

namespace App\Services\Examination;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ScoringFormPresenter
{
    public function present(ExamScore $scoring, ExamRegistration $examRegistration, Collection $formItems, bool $forDosenPanel = false): array
    {
        $scoring->loadMissing(['registration.student', 'registration.examtype', 'lecture']);

        $tanggalSekarang = Carbon::now()->isoFormat('Y-MM-DD');
        $examStartAt = Carbon::parse(
            $examRegistration->exam_date->format('Y-m-d').' '.trim((string) $examRegistration->exam_time)
        );

        $examNotStartedYet = false;

        if ($examRegistration->exam_date) {
            $examNotStartedYet = filled($examRegistration->exam_time)
                ? Carbon::now()->lt($examStartAt)
                : Carbon::now()->startOfDay()->lt($examRegistration->exam_date->startOfDay());
        }

        $adminUnlockedEdit = $this->isDosenScoringEditUnlocked($scoring);

        $availableCheck = ($examRegistration->exam_date < $tanggalSekarang && $examRegistration->pass_exam)
            && ! Auth::user()->can('force edit score')
            && ! $adminUnlockedEdit;

        $dosenScoringLocked = $forDosenPanel
            && $this->isDosenScoringEditBlocked($scoring, $examRegistration, $examStartAt);

        $scoreCols = ['score01', 'score02', 'score03', 'score04', 'score05'];
        $filledScores = [];

        foreach ($scoreCols as $col) {
            $value = $scoring->{$col};

            if ($value !== null && $value !== '') {
                $filledScores[] = (float) $value;
            }
        }

        $filledCount = count($filledScores);
        $hasStoredGrade = filled($scoring->grade);

        if ($filledCount === 5) {
            $initGrade = array_sum($filledScores) / 5;
        } elseif ($hasStoredGrade) {
            $initGrade = (float) $scoring->grade;
        } elseif ($filledCount > 0) {
            $initGrade = array_sum($filledScores) / $filledCount;
        } else {
            $initGrade = null;
        }

        $hasScores = $initGrade !== null;
        $initLetter = '';

        if ($hasScores && filled($scoring->letter)) {
            $initLetter = $scoring->letter;
        } elseif ($hasScores) {
            $initLetter = app(ExamScoreUpdater::class)->convertToLetter($initGrade);
        }

        $examType = $scoring->registration->exam_type_id;

        return [
            'scoring' => $scoring,
            'examregistration' => $examRegistration,
            'form_items' => $formItems,
            'exam_not_started_yet' => $examNotStartedYet,
            'available_check' => $availableCheck,
            'dosen_scoring_locked' => $dosenScoringLocked,
            'dosen_scoring_unlocked_by_admin' => $forDosenPanel && $adminUnlockedEdit,
            'form_disabled' => $forDosenPanel ? $dosenScoringLocked : $availableCheck,
            'save_button_label' => $dosenScoringLocked ? 'Dikunci' : 'Simpan Penilaian',
            'has_scores' => $hasScores,
            'init_grade' => $initGrade,
            'init_letter' => $initLetter,
            'grades_map' => $this->gradesMap(),
            'pass_verdict' => match ($examType) {
                1 => 'Rencana penelitian layak dilanjutkan untuk diteliti',
                2 => 'Seminar Hasil Penelitian layak disidangkan',
                default => 'Mahasiswa dinyatakan LULUS',
            },
            'fail_verdict' => match ($examType) {
                1 => 'Rencana penelitian tidak layak dilanjutkan untuk diteliti',
                2 => 'Seminar Hasil Penelitian tidak layak disidangkan',
                default => 'Mahasiswa dinyatakan TIDAK LULUS',
            },
            'init_pass' => $hasScores ? ($initGrade >= 37 ? 1 : 0) : ($scoring->pass_approved ?? ''),
            'notice_state' => ! $hasScores ? 'pending' : ($initGrade >= 37 ? 'pass' : 'fail'),
            'scoring_id' => $scoring->id,
            'user_id' => auth()->id(),
        ];
    }

    public function isDosenScoringLocked(ExamScore $scoring, Carbon $examStartAt): bool
    {
        if (Auth::user()->can('force edit score')) {
            return false;
        }

        return $this->isDosenScoringTimeLocked($scoring, $examStartAt);
    }

    public function isDosenScoringEditBlocked(
        ExamScore $scoring,
        ExamRegistration $examRegistration,
        Carbon $examStartAt,
    ): bool {
        if (Auth::user()->can('force edit score')) {
            return false;
        }

        if ($this->isDosenScoringEditUnlocked($scoring)) {
            return false;
        }

        if ($this->isDosenScoringTimeLocked($scoring, $examStartAt)) {
            return true;
        }

        $tanggalSekarang = Carbon::now()->isoFormat('Y-MM-DD');

        return $examRegistration->exam_date < $tanggalSekarang && $examRegistration->pass_exam;
    }

    public function isDosenScoringTimeLocked(ExamScore $scoring, Carbon $examStartAt): bool
    {
        if (! filled($scoring->grade)) {
            return false;
        }

        if (filled($scoring->scoring_edit_unlocked_at)) {
            return false;
        }

        return Carbon::now()->gte($examStartAt->copy()->addHours(24));
    }

    public function isDosenScoringEditUnlocked(ExamScore $scoring): bool
    {
        return filled($scoring->scoring_edit_unlocked_at);
    }

    private function gradesMap(): array
    {
        return [
            'A' => ['min' => 85, 'max' => 100, 'mid' => 92],
            'A-' => ['min' => 77, 'max' => 84, 'mid' => 80],
            'B+' => ['min' => 69, 'max' => 76, 'mid' => 72],
            'B' => ['min' => 61, 'max' => 68, 'mid' => 64],
            'B-' => ['min' => 53, 'max' => 60, 'mid' => 56],
            'C+' => ['min' => 45, 'max' => 52, 'mid' => 48],
            'C' => ['min' => 37, 'max' => 44, 'mid' => 40],
            'C-' => ['min' => 29, 'max' => 36, 'mid' => 32],
            'D' => ['min' => 21, 'max' => 28, 'mid' => 24],
            'E' => ['min' => 0, 'max' => 20, 'mid' => 10],
        ];
    }
}
