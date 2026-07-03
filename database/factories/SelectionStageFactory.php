<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SelectionStageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stage_order' => 1,
            'guide1_id' => null,
            'guide2_id' => null,
            'final' => false,
        ];
    }

    public function final(): static
    {
        return $this->state(fn () => ['final' => true]);
    }
}
