<?php

namespace Database\Factories;

use App\Models\GuideExaminer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory sementara untuk pengujian / seeding lokal.
 *
 * @extends Factory<GuideExaminer>
 */
class GuideExaminerFactory extends Factory
{
    protected $model = GuideExaminer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::role('mahasiswa')->inRandomOrder()->value('id'),
            'year_generation' => (string) fake()->numberBetween(2018, (int) date('Y')),
            'examiner1_id' => null,
            'examiner2_id' => null,
            'examiner3_id' => null,
            'guide1_id' => null,
            'guide2_id' => null,
            'chief_id' => null,
            'proposal_date' => null,
            'seminar_date' => null,
            'thesis_date' => null,
            'doc' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (GuideExaminer $guideExaminer) {
            $lecturers = User::role('dosen')->pluck('id');

            if ($lecturers->isEmpty()) {
                return;
            }

            $guideExaminer->examiner1_id ??= $lecturers->get(0);
            $guideExaminer->examiner2_id ??= $lecturers->get(1);
            $guideExaminer->examiner3_id ??= $lecturers->get(2);
            $guideExaminer->guide1_id ??= $lecturers->get(3);
            $guideExaminer->guide2_id ??= $lecturers->get(4);
            $guideExaminer->chief_id ??= $guideExaminer->examiner1_id;
        });
    }

    public function forStudent(User $student): static
    {
        return $this->state(fn () => [
            'user_id' => $student->id,
        ]);
    }

    public function withProposal(?string $date = null): static
    {
        return $this->state(fn () => [
            'proposal_date' => $date ?? fake()->date(),
        ]);
    }

    public function withSeminar(?string $date = null): static
    {
        return $this->state(fn () => [
            'seminar_date' => $date ?? fake()->date(),
        ]);
    }

    public function graduated(?string $date = null): static
    {
        return $this->state(fn () => [
            'proposal_date' => fake()->date(),
            'seminar_date' => fake()->date(),
            'thesis_date' => $date ?? fake()->date(),
        ]);
    }
}
