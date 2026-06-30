<?php

namespace Tests\Feature;

use App\Models\GuideExaminer;
use App\Models\NuirProposal;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;

class NuirStageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $mahasiswa;

    protected User $dosen1;

    protected User $dosen2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        $this->seedGuideAllocation($this->dosen1);
        $this->seedGuideAllocation($this->dosen2);
    }

    public function test_stage2_store_langsung_content_ok_tanpa_review_dbs(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', ['title' => 'Judul Saya'])
            ->assertRedirect();

        $sub = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();
        $this->assertEquals('content_ok', $sub->status);
    }

    public function test_stage2_novelty_tidak_diperlukan(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', ['title' => 'Judul Saja'])
            ->assertRedirect()
            ->assertSessionMissing('errors');

        $this->assertDatabaseHas('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'novelty' => null,
        ]);
    }

    public function test_stage2_mahasiswa_langsung_bisa_proposal_setelah_store(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', ['title' => 'Judul Saja']);

        $sub = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $sub->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));
    }

    public function test_stage2_judul_reuse_saat_proposal_ditolak(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $dosenBaru = User::factory()->create()->assignRole('dosen');
        $this->seedGuideAllocation($dosenBaru);

        NuirProposal::factory()->guide2Rejected('tidak bisa')->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $sub->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $dosenBaru->id,
            ])
            ->assertRedirect();

        $this->assertCount(2, NuirProposal::where('nuir_submission_id', $sub->id)->get());
    }

    public function test_stage3_mahasiswa_tidak_lihat_form_nuir(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->followingRedirects()
            ->get('/nuir/submission')
            ->assertOk()
            ->assertSeeText('tidak memerlukan pengajuan NUIR');
    }

    public function test_stage3_mahasiswa_tidak_dapat_store_submission(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', ['title' => 'Judul'])
            ->assertForbidden();
    }

    public function test_stage3_tidak_ada_proposal_yang_bisa_dibuat(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get('/nuir/proposal/create')
            ->assertForbidden();
    }
}
