<?php

namespace Tests\Feature;

use App\Filament\Dbs\Resources\NuirSubmissionResource;
use App\Models\GuideExaminer;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $dbs;

    protected User $mahasiswa;

    protected NuirSetting $setting;

    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->dbs = User::factory()->create()->assignRole('dbs');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($this->mahasiswa)
            ->create(['year_generation' => '2022']);
        $this->setting = NuirSetting::factory()->create([
            'year_generation' => '2022', 'stage' => 1,
            'active' => true, 'min_references_approved' => 2,
        ]);
        $this->submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
    }

    public function test_dbs_dapat_melihat_daftar_submission(): void
    {
        $this->actingAs($this->dbs)
            ->get('/setting/nuir/submissions')
            ->assertRedirect(NuirSubmissionResource::getUrl('index', panel: 'dbs'));

        $this->actingAs($this->dbs)
            ->get(NuirSubmissionResource::getUrl('index', panel: 'dbs'))
            ->assertOk();
    }

    public function test_mahasiswa_tidak_dapat_akses_review(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get('/setting/nuir/submissions')
            ->assertForbidden();
    }

    public function test_dbs_dapat_approve_referensi(): void
    {
        $ref = NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->dbs)
            ->patch("/setting/nuir/references/{$ref->id}", [
                'ref_approved' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($ref->fresh()->ref_approved);
    }

    public function test_dbs_reject_referensi_wajib_isi_catatan(): void
    {
        $ref = NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 1,
        ]);

        $this->actingAs($this->dbs)
            ->patch("/setting/nuir/references/{$ref->id}", [
                'ref_approved' => false,
                'ref_note' => '',
            ])
            ->assertSessionHasErrors('ref_note');
    }

    public function test_dbs_tidak_dapat_set_content_ok_jika_referensi_kurang(): void
    {
        $this->actingAs($this->dbs)
            ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                'action' => 'content_ok',
                'dbs_note' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertEquals('submitted', $this->submission->fresh()->status);
    }

    public function test_dbs_dapat_set_content_ok_jika_referensi_cukup(): void
    {
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 1]);
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 2]);

        $this->actingAs($this->dbs)
            ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                'action' => 'content_ok',
                'dbs_note' => 'Semua oke',
            ])
            ->assertRedirect();

        $this->assertEquals('content_ok', $this->submission->fresh()->status);
        $this->assertEquals($this->dbs->id, $this->submission->fresh()->dbs_reviewer_id);
    }

    public function test_dbs_dapat_minta_revisi(): void
    {
        $this->actingAs($this->dbs)
            ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                'action' => 'revision',
                'dbs_note' => 'Referensi masih kurang berkualitas',
            ])
            ->assertRedirect();

        $this->assertEquals('revision', $this->submission->fresh()->status);
        $this->assertEquals('Referensi masih kurang berkualitas', $this->submission->fresh()->dbs_note);
    }

    public function test_minta_revisi_wajib_isi_dbs_note(): void
    {
        $this->actingAs($this->dbs)
            ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                'action' => 'revision',
                'dbs_note' => '',
            ])
            ->assertSessionHasErrors('dbs_note');
    }

    public function test_keputusan_referensi_diversi_lama_tidak_berubah_saat_versi_baru_dibuat(): void
    {
        $ref = NuirReference::factory()->rejected('link tidak valid')->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 1,
        ]);
        $v2 = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'parent_submission_id' => $this->submission->id,
            'version' => 2,
            'status' => 'draft',
        ]);
        NuirReference::factory()->create(['nuir_submission_id' => $v2->id, 'ref_order' => 1]);

        $this->assertFalse($ref->fresh()->ref_approved);
        $this->assertEquals('link tidak valid', $ref->fresh()->ref_note);
    }
}
