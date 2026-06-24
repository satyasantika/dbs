<?php

namespace Database\Factories;

use App\Models\NuirSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NuirSetting>
 */
class NuirSettingFactory extends Factory
{
    protected $model = NuirSetting::class;

    public function definition(): array
    {
        return [
            'year_generation' => '2022',
            'stage' => 1,
            'active' => true,
            'deadline' => null,
            'min_references_approved' => 10,
            'max_chars_novelty' => null,
            'max_chars_urgency' => null,
            'max_chars_impact' => null,
        ];
    }

    public function stage2(): static
    {
        return $this->state(fn () => ['stage' => 2]);
    }

    public function stage3(): static
    {
        return $this->state(fn () => ['stage' => 3]);
    }

    public function withDeadline(string $date): static
    {
        return $this->state(fn () => ['deadline' => $date]);
    }
}
