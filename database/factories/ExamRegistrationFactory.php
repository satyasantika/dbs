<?php

namespace Database\Factories;

use App\Models\ExamRegistration;
use App\Models\ExamType;
use App\Models\GuideExaminer;
use App\Models\User;
use App\Services\Examination\ExamRegistrationExaminerSync;
use App\Services\Examination\ExamScoreUpdater;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk simulasi exam_registrations — otomatis sinkron ke guide_examiners & exam_scores.
 *
 * @extends Factory<ExamRegistration>
 */
class ExamRegistrationFactory extends Factory
{
    protected $model = ExamRegistration::class;

    protected bool $shouldSync = true;

    public function definition(): array
    {
        return [
            'exam_type_id' => ExamType::query()->inRandomOrder()->value('id'),
            'registration_order' => 1,
            'user_id' => User::role('mahasiswa')->inRandomOrder()->value('id'),
            'examiner1_id' => null,
            'examiner2_id' => null,
            'examiner3_id' => null,
            'guide1_id' => null,
            'guide2_id' => null,
            'chief_id' => null,
            'exam_date' => fake()->date(),
            'exam_time' => fake()->time('H:i:s'),
            'title' => fake()->sentence(6),
            'ipk' => fake()->randomFloat(2, 2.5, 4.0),
            'room' => 'R'.fake()->numberBetween(101, 305),
            'pass_exam' => false,
        ];
    }

    public function configure(): static
    {
        return $this
            ->afterMaking(function (ExamRegistration $registration) {
                $lecturers = User::role('dosen')->pluck('id');

                if ($lecturers->isEmpty()) {
                    return;
                }

                $registration->examiner1_id ??= $lecturers->get(0);
                $registration->examiner2_id ??= $lecturers->get(1);
                $registration->examiner3_id ??= $lecturers->get(2);
                $registration->guide1_id ??= $lecturers->get(3);
                $registration->guide2_id ??= $lecturers->get(4);
                $registration->chief_id ??= $lecturers->get(0);
            })
            ->afterCreating(function (ExamRegistration $registration) {
                if (! $this->shouldSync) {
                    return;
                }

                app(ExamRegistrationExaminerSync::class)->syncFromRegistration($registration->fresh());
            });
    }

    public function withoutSync(): static
    {
        return tap(clone $this, fn (self $factory) => $factory->shouldSync = false);
    }

    public function forStudent(User $student): static
    {
        return $this->state(fn () => [
            'user_id' => $student->id,
        ]);
    }

    public function forExamType(ExamType|int $examType, int $registrationOrder = 1): static
    {
        $examTypeId = $examType instanceof ExamType ? $examType->id : $examType;

        return $this->state(fn () => [
            'exam_type_id' => $examTypeId,
            'registration_order' => $registrationOrder,
        ]);
    }

    public function forGuideExaminer(GuideExaminer $guideExaminer, ExamType|int|null $examType = null, int $registrationOrder = 1): static
    {
        $examTypeId = match (true) {
            $examType instanceof ExamType => $examType->id,
            is_int($examType) => $examType,
            default => ExamType::query()->inRandomOrder()->value('id'),
        };

        return $this->state(fn () => [
            'user_id' => $guideExaminer->user_id,
            'exam_type_id' => $examTypeId,
            'registration_order' => $registrationOrder,
            'examiner1_id' => $guideExaminer->examiner1_id,
            'examiner2_id' => $guideExaminer->examiner2_id,
            'examiner3_id' => $guideExaminer->examiner3_id,
            'guide1_id' => $guideExaminer->guide1_id,
            'guide2_id' => $guideExaminer->guide2_id,
            'chief_id' => $guideExaminer->chief_id,
        ]);
    }

    public function invited(): static
    {
        return $this
            ->state(fn () => [
                'invited_at' => now()->subDays(fake()->numberBetween(1, 14)),
            ])
            ->afterCreating(function (ExamRegistration $registration) {
                $registration->fresh()->syncInvitedScheduleSnapshot();
            });
    }

    public function sentToStudent(): static
    {
        return $this->state(fn () => [
            'sent_at' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }

    public function fullyScored(?float $grade = null): static
    {
        return $this->afterCreating(function (ExamRegistration $registration) use ($grade) {
            $updater = app(ExamScoreUpdater::class);

            foreach ($registration->fresh()->examScores as $score) {
                $finalGrade = $grade ?? fake()->randomFloat(2, 75, 95);
                $updater->applyAdminFinalGrade($score, $finalGrade);
            }
        });
    }

    public function partiallyScored(): static
    {
        return $this->afterCreating(function (ExamRegistration $registration) {
            $updater = app(ExamScoreUpdater::class);
            $scores = $registration->fresh()->examScores;

            if ($scores->count() <= 1) {
                return;
            }

            foreach ($scores->take($scores->count() - 1) as $score) {
                $updater->applyAdminFinalGrade($score, fake()->randomFloat(2, 70, 90));
            }
        });
    }
}
