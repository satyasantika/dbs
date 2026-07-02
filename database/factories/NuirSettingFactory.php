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
            'max_references' => 10,
            'min_words_title' => 3,
            'max_words_title' => 20,
            'min_words_novelty' => 12,
            'max_words_novelty' => 300,
            'min_words_urgency' => 12,
            'max_words_urgency' => 300,
            'min_words_impact' => 12,
            'max_words_impact' => 300,
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
