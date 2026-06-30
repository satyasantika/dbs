<?php

namespace Database\Factories;

use App\Models\NuirSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<NuirSubmission>
 */
class NuirSubmissionFactory extends Factory
{
    protected $model = NuirSubmission::class;

    public function definition(): array
    {
        return [
            'user_id' => fn () => self::createUser()->id,
            'year_generation' => '2022',
            'parent_submission_id' => null,
            'version' => 1,
            'title' => fake()->sentence(),
            'novelty' => null,
            'urgency' => null,
            'impact' => null,
            'status' => 'draft',
            'dbs_reviewer_id' => null,
            'dbs_note' => null,
            'dbs_reviewed_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => 'submitted']);
    }

    public function contentOk(): static
    {
        return $this->state(fn () => ['status' => 'content_ok']);
    }

    public function finalized(): static
    {
        return $this->state(fn () => ['status' => 'finalized']);
    }

    public function titleSlot(): static
    {
        return $this->state(fn () => [
            'status' => 'title_slot',
            'novelty' => null,
            'urgency' => null,
            'impact' => null,
        ]);
    }

    public function withNUI(): static
    {
        return $this->state(fn () => [
            'novelty' => fake()->paragraph(5),
            'urgency' => fake()->paragraph(5),
            'impact' => fake()->paragraph(5),
        ]);
    }

    public function revision(): static
    {
        return $this->state(fn () => [
            'status' => 'revision',
            'dbs_note' => fake()->sentence(),
        ]);
    }

    public static function createUser(): User
    {
        return User::create([
            'name' => fake()->name(),
            'username' => fake()->unique()->numerify('20########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ]);
    }
}
