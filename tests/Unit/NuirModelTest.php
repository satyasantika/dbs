<?php

namespace Tests\Unit;

use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_nuir_setting_active_scope_filters_correctly(): void
    {
        NuirSetting::factory()->create(['active' => true, 'year_generation' => '2022']);
        NuirSetting::factory()->create(['active' => false, 'year_generation' => '2023']);

        $this->assertCount(1, NuirSetting::active()->get());
    }

    public function test_nuir_setting_active_scope_menghormati_rentang_tanggal_stage(): void
    {
        // Tanpa rentang tanggal (null) — tetap aktif selama toggle active=true.
        NuirSetting::factory()->create([
            'active' => true, 'year_generation' => '2020',
            'stage_starts_at' => null, 'deadline' => null,
        ]);
        // Belum masuk rentang (mulai besok).
        NuirSetting::factory()->create([
            'active' => true, 'year_generation' => '2021',
            'stage_starts_at' => now()->addDay(), 'deadline' => now()->addMonth(),
        ]);
        // Sudah lewat rentang (berakhir kemarin).
        NuirSetting::factory()->create([
            'active' => true, 'year_generation' => '2023',
            'stage_starts_at' => now()->subMonth(), 'deadline' => now()->subDay(),
        ]);
        // Sedang dalam rentang.
        NuirSetting::factory()->create([
            'active' => true, 'year_generation' => '2024',
            'stage_starts_at' => now()->subDay(), 'deadline' => now()->addDay(),
        ]);

        $activeYears = NuirSetting::active()->pluck('year_generation')->sort()->values()->all();

        $this->assertSame(['2020', '2024'], $activeYears);
    }

    public function test_nuir_submission_is_editable_only_in_draft_and_revision(): void
    {
        $titleSlot = NuirSubmission::factory()->titleSlot()->create();
        $draft = NuirSubmission::factory()->create(['status' => 'draft']);
        $revision = NuirSubmission::factory()->create(['status' => 'revision']);
        $submitted = NuirSubmission::factory()->create(['status' => 'submitted']);
        $ok = NuirSubmission::factory()->create(['status' => 'content_ok']);

        $this->assertTrue($titleSlot->isEditable());
        $this->assertTrue($titleSlot->isTitleSlot());
        $this->assertTrue($draft->isEditable());
        $this->assertTrue($revision->isEditable());
        $this->assertFalse($submitted->isEditable());
        $this->assertFalse($ok->isEditable());
    }

    public function test_nuir_submission_locked_seats_from_accepted_proposals(): void
    {
        $sub = NuirSubmission::factory()->create();
        NuirProposal::factory()->guide1Accepted()->guide2Rejected('x')->create([
            'nuir_submission_id' => $sub->id,
        ]);

        $locked = $sub->fresh()->lockedSeats();
        $this->assertNotNull($locked['guide1']);
        $this->assertNull($locked['guide2']);
    }

    public function test_nuir_submission_references_editable_before_content_ok(): void
    {
        $draft = NuirSubmission::factory()->create(['status' => 'draft']);
        $submitted = NuirSubmission::factory()->create(['status' => 'submitted']);
        $revision = NuirSubmission::factory()->create(['status' => 'revision']);
        $ok = NuirSubmission::factory()->create(['status' => 'content_ok']);
        $finalized = NuirSubmission::factory()->create(['status' => 'finalized']);

        $this->assertTrue($draft->isReferencesEditable());
        $this->assertTrue($submitted->isReferencesEditable());
        $this->assertTrue($revision->isReferencesEditable());
        $this->assertFalse($ok->isReferencesEditable());
        $this->assertFalse($finalized->isReferencesEditable());
    }

    public function test_nuir_submission_version_chain_via_parent(): void
    {
        $v1 = NuirSubmission::factory()->create(['version' => 1]);
        $v2 = NuirSubmission::factory()->create([
            'parent_submission_id' => $v1->id,
            'version' => 2,
        ]);

        $this->assertEquals($v1->id, $v2->parentSubmission->id);
    }

    public function test_nuir_reference_belongs_to_submission(): void
    {
        $sub = NuirSubmission::factory()->create();
        $ref = NuirReference::factory()->create(['nuir_submission_id' => $sub->id]);

        $this->assertEquals($sub->id, $ref->submission->id);
    }

    public function test_nuir_proposal_is_both_accepted_helper(): void
    {
        $proposal = NuirProposal::factory()->bothAccepted()->create();
        $this->assertTrue($proposal->isBothAccepted());

        $pending = NuirProposal::factory()->create();
        $this->assertFalse($pending->isBothAccepted());
    }

    public function test_nuir_submission_has_active_final_proposal(): void
    {
        $sub = NuirSubmission::factory()->create();
        NuirProposal::factory()->bothAccepted()->create(['nuir_submission_id' => $sub->id]);

        $this->assertTrue($sub->hasActiveFinalProposal());
    }

    public function test_nuir_reference_unique_order_per_submission(): void
    {
        $sub = NuirSubmission::factory()->create();
        NuirReference::factory()->create(['nuir_submission_id' => $sub->id, 'ref_order' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        NuirReference::factory()->create(['nuir_submission_id' => $sub->id, 'ref_order' => 1]);
    }

    public function test_nuir_submission_reference_validation_status(): void
    {
        $sub = NuirSubmission::factory()->create();

        $this->assertSame(NuirSubmission::REF_VALIDATION_NOT_STARTED, $sub->referenceValidationStatus());

        NuirReference::factory()->create(['nuir_submission_id' => $sub->id, 'ref_order' => 1, 'ref_approved' => null]);
        NuirReference::factory()->create(['nuir_submission_id' => $sub->id, 'ref_order' => 2, 'ref_approved' => null]);
        $sub->load('references');

        $this->assertSame(NuirSubmission::REF_VALIDATION_NOT_STARTED, $sub->referenceValidationStatus());
        $this->assertSame('0/2', $sub->referenceValidationProgressLabel());

        NuirReference::where('nuir_submission_id', $sub->id)->where('ref_order', 1)->update(['ref_approved' => true]);
        $sub->load('references');

        $this->assertSame(NuirSubmission::REF_VALIDATION_IN_PROGRESS, $sub->referenceValidationStatus());
        $this->assertSame('1/2', $sub->referenceValidationProgressLabel());

        NuirReference::where('nuir_submission_id', $sub->id)->where('ref_order', 2)->update(['ref_approved' => false]);
        $sub->load('references');

        $this->assertSame(NuirSubmission::REF_VALIDATION_COMPLETE, $sub->referenceValidationStatus());
        $this->assertSame('2/2', $sub->referenceValidationProgressLabel());
    }
}
