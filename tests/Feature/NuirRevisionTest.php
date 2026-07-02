<?php

namespace Tests\Feature;

use App\Models\GuideExaminer;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;

    protected NuirSubmission $v1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $this->v1 = NuirSubmission::factory()->revision()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'version' => 1,
        ]);
    }

    public function test_mahasiswa_dapat_akses_form_revisi(): void
    {
        $this->actingAs($this->mahasiswa)
            ->followingRedirects()
            ->get("/nuir/submission/{$this->v1->id}/revise")
            ->assertOk();
    }

    public function test_mahasiswa_tidak_dapat_revisi_jika_status_bukan_revision(): void
    {
        $sub = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get("/nuir/submission/{$sub->id}/revise")
            ->assertForbidden();
    }

    public function test_revisi_membuat_versi_baru_dengan_parent_id(): void
    {
        $revisedTitle = 'Judul yang Sudah Direvisi';

        $this->actingAs($this->mahasiswa)
            ->post("/nuir/submission/{$this->v1->id}/revise", [
                'title' => $revisedTitle,
                'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
                'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
                'impact' => implode(' ', array_fill(0, 15, 'impact')),
                'references' => [],
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $v2 = NuirSubmission::where('parent_submission_id', $this->v1->id)->first();
        $this->assertNotNull($v2);
        $this->assertEquals(2, $v2->version);
        $this->assertEquals('draft', $v2->status);
        $this->assertEquals($revisedTitle, $v2->title);
    }

    public function test_versi_lama_tidak_berubah_setelah_revisi_dibuat(): void
    {
        $originalNote = $this->v1->dbs_note;

        $this->actingAs($this->mahasiswa)
            ->post("/nuir/submission/{$this->v1->id}/revise", [
                'title' => 'Judul yang Baru',
                'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
                'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
                'impact' => implode(' ', array_fill(0, 15, 'impact')),
                'references' => [],
            ]);

        $this->assertEquals('revision', $this->v1->fresh()->status);
        $this->assertEquals($originalNote, $this->v1->fresh()->dbs_note);
    }

    public function test_tidak_dapat_revisi_jika_sudah_ada_versi_anak(): void
    {
        NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'parent_submission_id' => $this->v1->id,
            'version' => 2,
            'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get("/nuir/submission/{$this->v1->id}/revise")
            ->assertForbidden();
    }

    public function test_referensi_ditolak_muncul_di_form_revisi(): void
    {
        NuirReference::factory()->rejected('Link tidak valid')->create([
            'nuir_submission_id' => $this->v1->id, 'ref_order' => 3,
        ]);

        $this->actingAs($this->mahasiswa)
            ->followingRedirects()
            ->get("/nuir/submission/{$this->v1->id}/revise")
            ->assertSee('Link tidak valid');
    }

    public function test_versi_baru_punya_referensi_sendiri(): void
    {
        NuirReference::factory()->rejected('Link tidak valid')->create([
            'nuir_submission_id' => $this->v1->id, 'ref_order' => 1,
        ]);

        $this->actingAs($this->mahasiswa)
            ->post("/nuir/submission/{$this->v1->id}/revise", [
                'title' => 'Judul yang Direvisi',
                'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
                'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
                'impact' => implode(' ', array_fill(0, 15, 'impact')),
                'references' => [
                    1 => [
                        'link_ojs' => 'https://ojs.baru.com', 'indexer_name' => 'Scopus',
                        'link_index' => 'https://scopus.com', 'link_drive' => 'https://drive.com',
                        'quote' => 'kutipan baru', 'relevance' => 'relevan baru',
                    ],
                ],
            ]);

        $v2 = NuirSubmission::where('parent_submission_id', $this->v1->id)->first();
        $this->assertCount(1, $v2->references);
        $this->assertEquals('https://ojs.baru.com', $v2->references->first()->link_ojs);
        $this->assertEquals('Link tidak valid',
            NuirReference::where('nuir_submission_id', $this->v1->id)->first()->ref_note);
    }
}
