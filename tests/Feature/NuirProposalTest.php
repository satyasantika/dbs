<?php

namespace Tests\Feature;

use App\Models\GuideExaminer;
use App\Models\NuirProposal;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirProposalTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;

    protected User $dosen1;

    protected User $dosen2;

    protected User $dosen3;

    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        $this->dosen3 = User::factory()->create()->assignRole('dosen');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $this->submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
    }

    public function test_mahasiswa_dapat_buat_proposal(): void
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
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
            'guide1_status' => 'pending',
            'guide2_status' => 'pending',
        ]);
    }

    public function test_guide1_dan_guide2_tidak_boleh_sama(): void
    {
        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $this->submission->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen1->id,
            ])
            ->assertSessionHasErrors('guide2_id');
    }

    public function test_tidak_dapat_proposal_jika_submission_masih_draft(): void
    {
        $draft = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022', 'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $draft->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertSessionHasErrors('nuir_submission_id');
    }

    public function test_dapat_proposal_jika_submission_submitted(): void
    {
        $submitted = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submitted->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));
    }

    public function test_tidak_dapat_buat_proposal_jika_sudah_ada_final(): void
    {
        NuirProposal::factory()->bothAccepted()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $this->submission->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen3->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_proposal_ditolak_sebelumnya_tetap_tersimpan_saat_buat_proposal_baru(): void
    {
        $rejected = NuirProposal::factory()->guide2Rejected('tidak sesuai bidang')->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $this->submission->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen3->id,
            ]);

        $this->assertDatabaseHas('nuir_proposals', ['id' => $rejected->id]);
        $this->assertEquals('rejected', $rejected->fresh()->guide2_status);
        $this->assertEquals('tidak sesuai bidang', $rejected->fresh()->guide2_note);
        $this->assertDatabaseHas('nuir_proposals', [
            'nuir_submission_id' => $this->submission->id,
            'guide2_id' => $this->dosen3->id,
        ]);
    }

    public function test_submission_id_harus_milik_mahasiswa_sendiri(): void
    {
        $mahasiswaLain = User::factory()->create()->assignRole('mahasiswa');
        $subLain = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $mahasiswaLain->id, 'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $subLain->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertSessionHasErrors('nuir_submission_id');
    }
}
