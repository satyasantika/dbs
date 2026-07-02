<?php

namespace Tests\Feature;

use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource as ManajerSubmissionResource;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource as ValidatorSubmissionResource;
use App\Models\GuideExaminer;
use App\Models\NuirAssignment;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirReferenceReview;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirAssignmentService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;

class NuirValidatorManajerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $manajer;

    protected User $validator;

    protected User $mahasiswa;

    protected User $dosen1;

    protected User $dosen2;

    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->manajer = User::factory()->create()->assignRole('manajer nuir');
        $this->validator = User::factory()->create()->assignRole('validator nuir');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');

        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true, 'min_references_approved' => 2]);
        $this->seedGuideAllocation($this->dosen1);
        $this->seedGuideAllocation($this->dosen2);

        $this->submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
    }

    public function test_manajer_dapat_akses_panel_filament(): void
    {
        $this->actingAs($this->manajer)
            ->get('/nuir-manajer')
            ->assertOk();
    }

    public function test_validator_dapat_akses_panel_filament(): void
    {
        $this->actingAs($this->validator)
            ->get('/nuir-validator')
            ->assertOk();
    }

    public function test_manajer_dapat_mendelegasikan_validator(): void
    {
        $service = app(NuirAssignmentService::class);

        $assignment = $service->assignValidator($this->submission, $this->validator, $this->manajer);

        $this->assertEquals($this->validator->id, $assignment->validator_id);
        $this->assertEquals($this->manajer->id, $assignment->assigned_by);
    }

    public function test_validator_hanya_melihat_submission_yang_didelegasikan(): void
    {
        NuirAssignment::create([
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);

        NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $otherSubmission = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::listUrl(ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED, panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee($this->submission->user->name);

        $visibleIds = ValidatorSubmissionResource::getEloquentQuery()->pluck('id')->all();
        $this->assertContains($this->submission->id, $visibleIds);
        $this->assertNotContains($otherSubmission->id, $visibleIds);
    }

    public function test_validator_dapat_setujui_dan_tolak_referensi(): void
    {
        NuirAssignment::create([
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);

        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);
        $this->assertTrue($ref->fresh()->ref_approved);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator(
            $ref,
            $this->validator,
            false,
            'Link tidak valid',
            ['link_ojs'],
        );
        $this->assertFalse($ref->fresh()->ref_approved);
        $this->assertEquals('Link tidak valid', $ref->fresh()->ref_note);
    }

    public function test_validator_tidak_dapat_review_submission_draft(): void
    {
        $draft = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
        ]);

        NuirAssignment::create([
            'nuir_submission_id' => $draft->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);

        $ref = NuirReference::factory()->create([
            'nuir_submission_id' => $draft->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->validator);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);
    }

    public function test_feedback_validator_tampil_ke_mahasiswa_tanpa_tunggu_revisi(): void
    {
        NuirReference::factory()->rejected('DOI tidak valid')->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSee('Diminta Revisi Validator')
            ->assertSee('DOI tidak valid');
    }

    public function test_mahasiswa_dapat_buat_proposal_saat_submitted(): void
    {
        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $this->submission->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));

        $this->assertDatabaseHas('nuir_proposals', [
            'nuir_submission_id' => $this->submission->id,
        ]);
    }

    public function test_calon_pembimbing_tidak_dapat_terima_sebelum_content_ok(): void
    {
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->dosen1)
            ->put("/nuir/dosen/{$proposal->id}/accept")
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertEquals('pending', $proposal->fresh()->guide1_status);
    }

    public function test_calon_pembimbing_dapat_terima_setelah_content_ok(): void
    {
        $this->submission->update(['status' => 'content_ok']);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        foreach (['novelty', 'urgency', 'impact'] as $field) {
            $this->actingAs($this->dosen1)
                ->patch("/nuir/dosen/{$proposal->id}/content", [
                    'field' => $field,
                    'approved' => '1',
                ]);
        }

        $this->assertEquals('accepted', $proposal->fresh()->guide1_status);
    }

    public function test_calon_pembimbing_dapat_review_referensi(): void
    {
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $ref = NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->dosen1)
            ->patch("/nuir/dosen/{$proposal->id}/references/{$ref->id}", [
                'approved' => 0,
                'note' => 'Kutipan kurang relevan',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('nuir_reference_reviews', [
            'nuir_reference_id' => $ref->id,
            'user_id' => $this->dosen1->id,
            'role' => NuirReferenceReview::ROLE_GUIDE1,
            'approved' => false,
            'note' => 'Kutipan kurang relevan',
        ]);
    }

    public function test_calon_pembimbing_melihat_permintaan_revisi(): void
    {
        $this->submission->update([
            'status' => 'revision',
            'dbs_note' => 'Perbaiki urgensi penelitian',
        ]);

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->dosen1)
            ->get("/nuir/dosen/{$proposal->id}")
            ->assertOk()
            ->assertSee('Permintaan Revisi NUIR')
            ->assertSee('Perbaiki urgensi penelitian');
    }

    public function test_manajer_dapat_akses_daftar_submission(): void
    {
        $this->actingAs($this->manajer)
            ->get(ManajerSubmissionResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee($this->submission->user->name);
    }
}
