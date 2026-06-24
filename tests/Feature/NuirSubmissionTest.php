<?php

namespace Tests\Feature;

use App\Models\GuideExaminer;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;

    protected User $dosen1;

    protected User $dbs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dbs = User::factory()->create()->assignRole('dbs');
        GuideExaminer::factory()->forStudent($this->mahasiswa)
            ->create(['year_generation' => '2022']);
    }

    public function test_mahasiswa_angkatan_aktif_dapat_akses_form(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get('/nuir/submission')
            ->assertOk();
    }

    public function test_mahasiswa_angkatan_tidak_aktif_tidak_dapat_akses_form(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => false]);

        $this->actingAs($this->mahasiswa)
            ->get('/nuir/submission')
            ->assertOk()
            ->assertSeeText('belum dibuka');
    }

    public function test_mahasiswa_tanpa_guide_examiner_tidak_dapat_akses_form(): void
    {
        $mahasiswaBaru = User::factory()->create()->assignRole('mahasiswa');
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($mahasiswaBaru)
            ->get('/nuir/submission')
            ->assertSeeText('belum dibuka');
    }

    public function test_mahasiswa_dapat_simpan_draft(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', [
                'title' => 'Judul Penelitian',
                'novelty' => str_repeat('a', 100),
                'urgency' => str_repeat('b', 100),
                'impact' => str_repeat('c', 100),
                'references' => [
                    1 => [
                        'link_ojs' => 'https://ojs.example.com/1',
                        'indexer_name' => 'Scopus',
                        'link_index' => 'https://scopus.com',
                        'link_drive' => 'https://drive.google.com/1',
                        'quote' => 'kutipan artikel 1 [page 5]',
                        'relevance' => 'relevan karena...',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'status' => 'draft',
            'title' => 'Judul Penelitian',
        ]);
        $this->assertDatabaseHas('nuir_references', [
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
        ]);
    }

    public function test_novelty_melebihi_batas_karakter_ditolak(): void
    {
        NuirSetting::factory()->create([
            'year_generation' => '2022', 'stage' => 1, 'active' => true,
            'max_chars_novelty' => 100,
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', [
                'title' => 'Judul',
                'novelty' => str_repeat('x', 101),
                'urgency' => 'urgency oke',
                'impact' => 'impact oke',
            ])
            ->assertSessionHasErrors('novelty');
    }

    public function test_submit_mengubah_status_menjadi_submitted(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$sub->id}/submit")
            ->assertRedirect();

        $this->assertEquals('submitted', $sub->fresh()->status);
    }

    public function test_submission_yang_sudah_submitted_tidak_bisa_diedit(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get("/nuir/submission/{$sub->id}/edit")
            ->assertForbidden();
    }

    public function test_dbs_tidak_dapat_akses_form_mahasiswa(): void
    {
        $this->actingAs($this->dbs)
            ->get('/nuir/submission')
            ->assertForbidden();
    }
}
