<?php

namespace Database\Factories;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\User;
use App\Services\Examination\ExamRegistrationExaminerSync;
use App\Services\Examination\ExamScoreUpdater;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk exam_scores — biasanya dibuat lewat sync dari exam_registrations,
 * lalu diisi nilai lewat state scored() / unscored().
 *
 * @extends Factory<ExamScore>
 */
class ExamScoreFactory extends Factory
{
    protected $model = ExamScore::class;

    public function definition(): array
    {
        return [
            'exam_registration_id' => ExamRegistration::factory(),
            'user_id' => User::role('dosen')->inRandomOrder()->value('id'),
            'examiner_order' => 1,
            'score01' => null,
            'score02' => null,
            'score03' => null,
            'score04' => null,
            'score05' => null,
            'grade' => null,
            'letter' => null,
            'revision' => null,
            'revision_note' => null,
            'pass_approved' => null,
        ];
    }

    public function forRegistrationSlot(ExamRegistration $registration, int $order): static
    {
        $field = ExamRegistrationExaminerSync::SLOT_FIELDS[$order] ?? null;
        $userId = $field ? $registration->{$field} : null;

        return $this->state(fn () => [
            'exam_registration_id' => $registration->id,
            'user_id' => $userId,
            'examiner_order' => $order,
        ]);
    }

    public function scored(?float $grade = null): static
    {
        return $this->afterCreating(function (ExamScore $score) use ($grade) {
            $finalGrade = $grade ?? fake()->randomFloat(2, 75, 95);

            app(ExamScoreUpdater::class)->applyAdminFinalGrade($score, $finalGrade);
        });
    }

    public function unscored(): static
    {
        return $this->state(fn () => [
            'score01' => null,
            'score02' => null,
            'score03' => null,
            'score04' => null,
            'score05' => null,
            'grade' => null,
            'letter' => null,
            'revision' => null,
            'revision_note' => null,
            'pass_approved' => null,
        ]);
    }
}
