<?php

namespace Database\Factories;

use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use Database\Factories\NuirSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NuirProposal>
 */
class NuirProposalFactory extends Factory
{
    protected $model = NuirProposal::class;

    public function definition(): array
    {
        return [
            'nuir_submission_id' => NuirSubmission::factory(),
            'guide1_id' => fn () => NuirSubmissionFactory::createUser()->id,
            'guide2_id' => fn () => NuirSubmissionFactory::createUser()->id,
            'guide1_status' => 'pending',
            'guide2_status' => 'pending',
            'guide1_note' => null,
            'guide2_note' => null,
            'guide1_responded_at' => null,
            'guide2_responded_at' => null,
            'final' => false,
        ];
    }

    public function guide1Accepted(): static
    {
        return $this->state(fn () => ['guide1_status' => 'accepted']);
    }

    public function guide2Accepted(): static
    {
        return $this->state(fn () => ['guide2_status' => 'accepted']);
    }

    public function guide1Rejected(string $note): static
    {
        return $this->state(fn () => [
            'guide1_status' => 'rejected',
            'guide1_note' => $note,
        ]);
    }

    public function guide2Rejected(string $note): static
    {
        return $this->state(fn () => [
            'guide2_status' => 'rejected',
            'guide2_note' => $note,
        ]);
    }

    public function bothAccepted(): static
    {
        return $this->state(fn () => [
            'guide1_status' => 'accepted',
            'guide2_status' => 'accepted',
            'final' => true,
        ]);
    }
}
