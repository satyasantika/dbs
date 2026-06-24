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

class NuirGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;

    protected User $dosen1;

    protected User $dosen2;

    protected User $dbs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        $this->dbs = User::factory()->create()->assignRole('dbs');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
    }

    public function test_mahasiswa_tidak_dapat_submit_setelah_deadline(): void
    {
        NuirSetting::factory()->withDeadline('2020-01-01')->create([
            'year_generation' => '2022', 'stage' => 1, 'active' => true,
        ]);
        $sub = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022', 'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$sub->id}/submit")
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertEquals('draft', $sub->fresh()->status);
    }

    public function test_mahasiswa_tidak_dapat_buat_proposal_setelah_deadline(): void
    {
        NuirSetting::factory()->withDeadline('2020-01-01')->create([
            'year_generation' => '2022', 'stage' => 1, 'active' => true,
        ]);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $sub->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_mahasiswa_tidak_dapat_buat_submission_baru_jika_sudah_finalized(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        NuirSubmission::factory()->finalized()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get('/nuir/submission/create')
            ->assertRedirect(route('nuir.submission.index'));
    }

    public function test_mahasiswa_tidak_dapat_buat_proposal_duplikat_pasangan_sama(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
            'guide1_status' => 'pending', 'guide2_status' => 'pending',
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $sub->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertSessionHasErrors();
    }

    public function test_dbs_dapat_melihat_monitor_semua_proposal(): void
    {
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
        ]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($this->dbs)
            ->get('/setting/nuir/proposals')
            ->assertOk();
    }

    public function test_dbs_dapat_force_finalize_jika_kedua_dosen_sudah_accept(): void
    {
        $ge = GuideExaminer::where('user_id', $this->mahasiswa->id)->first();
        $ge->update(['guide1_id' => null, 'guide2_id' => null]);

        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
            'guide1_status' => 'accepted', 'guide2_status' => 'accepted', 'final' => false,
        ]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($this->dbs)
            ->put("/setting/nuir/proposals/{$proposal->id}/finalize")
            ->assertRedirect();

        $this->assertTrue($proposal->fresh()->final);
        $this->assertEquals('finalized', $sub->fresh()->status);
        $this->assertEquals($this->dosen1->id, $ge->fresh()->guide1_id);
    }

    public function test_dbs_tidak_dapat_force_finalize_jika_masih_pending(): void
    {
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
        ]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($this->dbs)
            ->put("/setting/nuir/proposals/{$proposal->id}/finalize")
            ->assertForbidden();
    }

    public function test_mahasiswa_dashboard_menampilkan_card_nuir_jika_angkatan_aktif(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get('/dashboard')
            ->assertSeeText('Pengajuan NUIR');
    }

    public function test_mahasiswa_dashboard_tidak_menampilkan_nuir_jika_stage3(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get('/dashboard')
            ->assertDontSeeText('Pengajuan NUIR');
    }
}
