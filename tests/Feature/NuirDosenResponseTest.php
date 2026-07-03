<?php

namespace Tests\Feature;

use App\Models\GuideExaminer;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;

class NuirDosenResponseTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $mahasiswa;

    protected User $dosen1;

    protected User $dosen2;

    protected NuirProposal $proposal;

    protected GuideExaminer $ge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        $this->ge = GuideExaminer::factory()->forStudent($this->mahasiswa)
            ->create(['year_generation' => '2022', 'guide1_id' => null, 'guide2_id' => null]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $this->seedGuideAllocation($this->dosen1);
        $this->seedGuideAllocation($this->dosen2);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $this->proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);
    }

    public function test_dosen_dapat_lihat_proposal_yang_ditujukan(): void
    {
        $this->actingAs($this->dosen1)
            ->get('/nuir/dosen')
            ->assertRedirect(\App\Filament\Dosen\Resources\NuirSubmissionResource::getUrl('index', panel: 'dosen'));
    }

    public function test_dosen_tidak_dapat_lihat_proposal_yang_tidak_ditujukan(): void
    {
        $dosenLain = User::factory()->create()->assignRole('dosen');

        $this->actingAs($dosenLain)
            ->get("/nuir/dosen/{$this->proposal->id}")
            ->assertForbidden();
    }

    public function test_dosen1_dapat_menerima_proposal(): void
    {
        $this->approveAllNuiFields($this->dosen1);

        $this->assertEquals('accepted', $this->proposal->fresh()->guide1_status);
        $this->assertNotNull($this->proposal->fresh()->guide1_responded_at);
    }

    public function test_dosen_tidak_dapat_terima_proposal_sebelum_content_ok(): void
    {
        $submitted = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submitted->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->dosen1)
            ->put("/nuir/dosen/{$proposal->id}/accept")
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertEquals('pending', $proposal->fresh()->guide1_status);
    }

    public function test_dosen_tidak_dapat_respond_dua_kali(): void
    {
        $this->proposal->update(['guide1_status' => 'accepted']);

        $this->actingAs($this->dosen1)
            ->put("/nuir/dosen/{$this->proposal->id}/accept")
            ->assertForbidden();
    }

    public function test_dosen_dapat_menolak_dengan_catatan(): void
    {
        $this->actingAs($this->dosen2)
            ->put("/nuir/dosen/{$this->proposal->id}/reject", [
                'note' => 'Topik tidak sesuai keahlian saya',
            ])
            ->assertRedirect();

        $this->assertEquals('rejected', $this->proposal->fresh()->guide2_status);
        $this->assertEquals('Topik tidak sesuai keahlian saya', $this->proposal->fresh()->guide2_note);
    }

    public function test_menolak_tanpa_catatan_ditolak_validasi(): void
    {
        $this->actingAs($this->dosen1)
            ->put("/nuir/dosen/{$this->proposal->id}/reject", ['note' => ''])
            ->assertSessionHasErrors('note');
    }

    public function test_kedua_dosen_terima_memicu_final_dan_guide_examiners(): void
    {
        $this->approveAllNuiFields($this->dosen1);
        $this->approveAllNuiFields($this->dosen2);

        $proposal = $this->proposal->fresh();
        $this->assertTrue($proposal->final);
        $this->assertEquals('finalized', $proposal->submission->status);

        $ge = $this->ge->fresh();
        $this->assertEquals($this->dosen1->id, $ge->guide1_id);
        $this->assertEquals($this->dosen2->id, $ge->guide2_id);
    }

    public function test_sejarah_penolakan_proposal_tahap_1_tersimpan_saat_proposal_baru_dibuat(): void
    {
        $this->actingAs($this->dosen2)
            ->put("/nuir/dosen/{$this->proposal->id}/reject", ['note' => 'tidak sesuai']);

        $dosenBaru = User::factory()->create()->assignRole('dosen');
        $sub = $this->proposal->submission;
        NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $dosenBaru->id,
        ]);

        $this->assertEquals('rejected', $this->proposal->fresh()->guide2_status);
        $this->assertEquals('tidak sesuai', $this->proposal->fresh()->guide2_note);
        $this->assertCount(2, NuirProposal::where('nuir_submission_id', $sub->id)->get());
    }

    public function test_mahasiswa_tidak_dapat_respond_proposal(): void
    {
        $this->actingAs($this->mahasiswa)
            ->put("/nuir/dosen/{$this->proposal->id}/accept")
            ->assertForbidden();
    }

    /** @param list<string> $fields */
    private function approveAllNuiFields(User $dosen, ?array $fields = null): void
    {
        $fields ??= NuirContentReview::FIELDS;

        foreach ($fields as $field) {
            $this->actingAs($dosen)
                ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                    'field' => $field,
                    'approved' => '1',
                ]);
        }
    }
}
