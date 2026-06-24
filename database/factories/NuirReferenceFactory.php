<?php

namespace Database\Factories;

use App\Models\NuirReference;
use App\Models\NuirSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NuirReference>
 */
class NuirReferenceFactory extends Factory
{
    protected $model = NuirReference::class;

    public function definition(): array
    {
        return [
            'nuir_submission_id' => NuirSubmission::factory(),
            'ref_order' => 1,
            'link_ojs' => fake()->optional()->url(),
            'indexer_name' => fake()->optional()->word(),
            'link_index' => fake()->optional()->url(),
            'link_drive' => fake()->optional()->url(),
            'quote' => fake()->optional()->paragraph(),
            'relevance' => fake()->optional()->paragraph(),
            'ref_approved' => null,
            'ref_note' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['ref_approved' => true]);
    }

    public function rejected(string $note): static
    {
        return $this->state(fn () => [
            'ref_approved' => false,
            'ref_note' => $note,
        ]);
    }
}
