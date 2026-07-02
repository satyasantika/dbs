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
            ->followingRedirects()
            ->get('/nuir/submission')
            ->assertOk();
    }

    public function test_mahasiswa_angkatan_tidak_aktif_tidak_dapat_akses_form(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => false]);

        $this->actingAs($this->mahasiswa)
            ->followingRedirects()
            ->get('/nuir/submission')
            ->assertOk()
            ->assertSeeText('belum dibuka');
    }

    public function test_mahasiswa_tanpa_guide_examiner_tidak_dapat_akses_form(): void
    {
        $mahasiswaBaru = User::factory()->create()->assignRole('mahasiswa');
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($mahasiswaBaru)
            ->followingRedirects()
            ->get('/nuir/submission')
            ->assertSeeText('belum dibuka');
    }

    public function test_mahasiswa_dapat_simpan_draft(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', [
                'title' => 'Judul Penelitian yang Baru',
                'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
                'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
                'impact' => implode(' ', array_fill(0, 15, 'impact')),
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
            'title' => 'Judul Penelitian yang Baru',
        ]);
        $this->assertDatabaseHas('nuir_references', [
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
        ]);
    }

    public function test_mahasiswa_dapat_perbarui_draft_dan_referensi(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
            'title' => 'Judul Lama',
        ]);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$sub->id}", [
                'title' => 'Judul yang Sudah Diperbarui',
                'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
                'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
                'impact' => implode(' ', array_fill(0, 15, 'impact')),
                'references' => [
                    2 => [
                        'link_ojs' => 'https://ojs.example.com/2',
                        'indexer_name' => 'WoS',
                        'link_index' => 'https://wos.example.com',
                        'link_drive' => 'https://drive.google.com/2',
                        'quote' => 'Kutipan referensi kedua',
                        'relevance' => 'Sangat relevan',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nuir_submissions', [
            'id' => $sub->id,
            'title' => 'Judul yang Sudah Diperbarui',
        ]);
        $this->assertDatabaseHas('nuir_references', [
            'nuir_submission_id' => $sub->id,
            'ref_order' => 2,
            'quote' => 'Kutipan referensi kedua',
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

    public function test_submission_yang_sudah_submitted_tidak_bisa_edit_konten_tapi_bisa_kelola_referensi(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->followingRedirects()
            ->get("/nuir/submission/{$sub->id}/edit")
            ->assertOk()
            ->assertSee('Referensi')
            ->assertSee('Simpan Referensi #1');
    }

    public function test_mahasiswa_dapat_perbarui_referensi_pada_submission_submitted(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
        NuirReference::factory()->rejected('DOI tidak valid')->create([
            'nuir_submission_id' => $sub->id,
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
        ]);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$sub->id}", [
                'references' => [
                    1 => [
                        'link_ojs' => 'https://ojs.example.com/article/1',
                        'indexer_name' => 'Scopus',
                        'link_index' => 'https://scopus.example.com/1',
                        'link_drive' => 'https://drive.example.com/1',
                        'quote' => 'Kutipan diperbaiki',
                        'relevance' => 'Relevansi diperbaiki',
                    ],
                ],
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $ref = NuirReference::where('nuir_submission_id', $sub->id)->where('ref_order', 1)->first();
        $this->assertEquals('Kutipan diperbaiki', $ref->quote);
        $this->assertNull($ref->ref_approved);
        $this->assertNull($ref->ref_note);
        $this->assertEquals('submitted', $sub->fresh()->status);
    }

    public function test_submission_content_ok_tidak_bisa_kelola_referensi(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'content_ok',
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

    public function test_mahasiswa_buat_slot_judul_terlebih_dulu(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', [
                'title' => 'Judul Awal Slot',
                'title_only' => '1',
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $this->assertDatabaseHas('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'title' => 'Judul Awal Slot',
            'status' => 'title_slot',
        ]);
    }
}
