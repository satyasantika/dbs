<?php

namespace Database\Factories;

use App\Models\ExamRegistration;
use App\Models\ExamType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory sementara untuk pengujian / seeding lokal.
 *
 * @extends Factory<ExamRegistration>
 */
class ExamRegistrationFactory extends Factory
{
    protected $model = ExamRegistration::class;

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
        return $this->afterMaking(function (ExamRegistration $registration) {
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
        });
    }
}
