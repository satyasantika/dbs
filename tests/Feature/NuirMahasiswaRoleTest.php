<?php

namespace Tests\Feature;

use App\Models\GuideAllocation;
use App\Models\GuideExaminer;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirProposalService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;

class NuirMahasiswaRoleTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $mahasiswa;

    protected User $dosenP1;

    protected User $dosenP2;

    protected User $dosenP2Only;

    protected NuirSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosenP1 = User::factory()->create()->assignRole('dosen');
        $this->dosenP2 = User::factory()->create()->assignRole('dosen');
        $this->dosenP2Only = User::factory()->create()->assignRole('dosen');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        $this->setting = NuirSetting::factory()->create([
            'year_generation' => '2022',
            'stage' => 1,
            'active' => true,
            'max_references' => 3,
        ]);
        $this->seedGuideAllocation($this->dosenP1);
        $this->seedGuideAllocation($this->dosenP2);
        GuideAllocation::create([
            'user_id' => $this->dosenP2Only->id,
            'year' => 2022,
            'guide1_quota' => 0,
            'guide2_quota' => 2,
            'guide1_filled' => 0,
            'guide2_filled' => 0,
            'active' => true,
        ]);
    }

    public function test_alur_lengkap_slot_judul_submit_dan_usulan_pembimbing(): void
    {
        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', ['title' => 'Judul Awal', 'title_only' => '1'])
            ->assertRedirect(route('nuir.submission.index'));

        $submission = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();
        $this->assertEquals('title_slot', $submission->status);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$submission->id}", [
                'title' => 'Judul Awal',
                'novelty' => str_repeat('n', 100),
                'urgency' => str_repeat('u', 100),
                'impact' => str_repeat('i', 100),
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $submission->refresh();
        $this->assertEquals('draft', $submission->status);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$submission->id}/submit")
            ->assertRedirect(route('nuir.submission.index'));

        $submission->refresh();
        $this->assertEquals('submitted', $submission->status);

        $submission->update(['status' => 'content_ok']);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->dosenP1->id,
                'guide2_id' => $this->dosenP2->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));

        $this->assertDatabaseHas('nuir_proposals', [
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide2_id' => $this->dosenP2->id,
        ]);
    }

    public function test_tidak_bisa_usulan_jika_nui_masih_diminta_revisi(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        NuirContentReview::create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $this->dosenP1->id,
            'role' => NuirContentReview::ROLE_GUIDE1,
            'field' => NuirContentReview::FIELD_NOVELTY,
            'approved' => false,
            'note' => 'Kurang spesifik',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->dosenP1->id,
                'guide2_id' => $this->dosenP2->id,
            ])
            ->assertSessionHasErrors('novelty');
    }

    public function test_mahasiswa_wajib_revisi_nui_per_elemen_yang_ditolak(): void
    {
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'novelty' => 'novelty lama',
        ]);

        NuirContentReview::create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $this->dosenP1->id,
            'role' => NuirContentReview::ROLE_GUIDE1,
            'field' => NuirContentReview::FIELD_NOVELTY,
            'approved' => false,
            'note' => 'Perlu diperjelas',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$submission->id}", [
                'novelty' => str_repeat('n', 120),
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $submission->refresh();
        $this->assertEquals(str_repeat('n', 120), $submission->novelty);
        $this->assertDatabaseMissing('nuir_content_reviews', [
            'nuir_submission_id' => $submission->id,
            'field' => NuirContentReview::FIELD_NOVELTY,
            'approved' => false,
        ]);
    }

    public function test_tidak_bisa_usulan_jika_referensi_masih_ditolak(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        NuirReference::factory()->rejected('Link rusak')->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->dosenP1->id,
                'guide2_id' => $this->dosenP2->id,
            ])
            ->assertSessionHasErrors('references');
    }

    public function test_mahasiswa_dapat_memperbaiki_referensi_ditolak(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        NuirReference::factory()->rejected('Link rusak')->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$submission->id}", [
                'references' => [
                    1 => [
                        'link_ojs' => 'https://ojs.fixed.com',
                        'indexer_name' => 'Scopus',
                        'link_index' => 'https://scopus.com/1',
                        'link_drive' => 'https://drive.com/1',
                        'quote' => 'kutipan',
                        'relevance' => 'relevan',
                    ],
                ],
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $ref = NuirReference::where('nuir_submission_id', $submission->id)->where('ref_order', 1)->first();
        $this->assertNull($ref->ref_approved);
        $this->assertNull($ref->ref_note);
        $this->assertEquals('https://ojs.fixed.com', $ref->link_ojs);
    }

    public function test_referensi_dibatasi_max_references_manager(): void
    {
        $submission = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
        ]);

        $references = [];
        for ($i = 1; $i <= 4; $i++) {
            $references[$i] = [
                'link_ojs' => "https://ojs.test/{$i}",
                'indexer_name' => 'Scopus',
                'link_index' => "https://index.test/{$i}",
                'link_drive' => "https://drive.test/{$i}",
                'quote' => 'kutipan',
                'relevance' => 'relevan',
            ];
        }

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$submission->id}", [
                'title' => $submission->title,
                'novelty' => str_repeat('n', 100),
                'urgency' => str_repeat('u', 100),
                'impact' => str_repeat('i', 100),
                'references' => $references,
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $this->assertEquals(3, NuirReference::where('nuir_submission_id', $submission->id)->count());
    }

    public function test_daftar_dosen_pembimbing_terfilter_kuota_per_posisi(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $service = app(NuirProposalService::class);
        $lockedSeats = $submission->lockedSeats();

        $p1Ids = $service->lecturersForSeat($this->mahasiswa, '2022', 1, $lockedSeats)->pluck('id')->all();
        $p2Ids = $service->lecturersForSeat($this->mahasiswa, '2022', 2, $lockedSeats)->pluck('id')->all();

        $this->assertContains($this->dosenP1->id, $p1Ids);
        $this->assertNotContains($this->dosenP2Only->id, $p1Ids);
        $this->assertContains($this->dosenP2Only->id, $p2Ids);
    }

    public function test_isi_ulang_kursi_kosong_dengan_dosen_pengganti_posisi_sama(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        NuirProposal::factory()->guide1Accepted()->guide2Rejected('tidak bisa')->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide2_id' => $this->dosenP2->id,
            'guide1_responded_at' => now(),
            'guide2_responded_at' => now(),
        ]);

        GuideAllocation::where('user_id', $this->dosenP1->id)->update(['guide1_filled' => 1]);
        GuideAllocation::where('user_id', $this->dosenP2->id)->update(['guide2_filled' => 0]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->dosenP1->id,
                'guide2_id' => $this->dosenP2Only->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));

        $latest = NuirProposal::where('nuir_submission_id', $submission->id)->latest('id')->first();
        $this->assertEquals('accepted', $latest->guide1_status);
        $this->assertEquals('pending', $latest->guide2_status);
        $this->assertEquals($this->dosenP2Only->id, $latest->guide2_id);
    }
}
