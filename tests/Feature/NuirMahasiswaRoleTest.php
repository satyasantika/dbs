<?php

namespace Tests\Feature;

use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Models\GuideAllocation;
use App\Models\GuideExaminer;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirMahasiswaWorkspaceService;
use App\Services\NuirProposalService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
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
            ->post('/nuir/submission', ['title' => 'Judul Awal Penelitian Terbaru', 'title_only' => '1'])
            ->assertRedirect(route('nuir.submission.index'));

        $submission = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();
        $this->assertEquals('title_slot', $submission->status);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$submission->id}", [
                'title' => 'Judul Awal Penelitian Terbaru',
                'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
                'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
                'impact' => implode(' ', array_fill(0, 15, 'impact')),
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

        $revisedNovelty = implode(' ', array_fill(0, 15, 'novelty'));

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$submission->id}", [
                'novelty' => $revisedNovelty,
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $submission->refresh();
        $this->assertEquals($revisedNovelty, $submission->novelty);
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
                'title' => filled($submission->title) ? $submission->title : 'Judul Penelitian yang Valid',
                'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
                'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
                'impact' => implode(' ', array_fill(0, 15, 'impact')),
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

    public function test_lecturer_seat_options_menampilkan_sisa_kuota_dan_menandai_habis(): void
    {
        GuideAllocation::where('user_id', $this->dosenP2Only->id)->update(['guide1_quota' => 0]);

        $options = app(NuirProposalService::class)->lecturerSeatOptions(
            $this->mahasiswa,
            '2022',
            1,
            ['guide1' => null, 'guide2' => null],
        );

        $p2Only = collect($options)->firstWhere('id', $this->dosenP2Only->id);
        $this->assertNotNull($p2Only);
        $this->assertSame(0, $p2Only['remaining_quota']);
        $this->assertFalse($p2Only['selectable']);

        $p1 = collect($options)->firstWhere('id', $this->dosenP1->id);
        $this->assertSame(2, $p1['remaining_quota']);
        $this->assertTrue($p1['selectable']);
    }

    public function test_workspace_menampilkan_sisa_kuota_pembimbing(): void
    {
        NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        GuideAllocation::where('user_id', $this->dosenP2Only->id)->update(['guide1_quota' => 0]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSee('sisa kuota P1: 2')
            ->assertSee('sisa kuota P1: 0');
    }

    public function test_propose_guide_ditolak_jika_kuota_habis(): void
    {
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        GuideAllocation::where('user_id', $this->dosenP2Only->id)->update(['guide1_quota' => 0]);

        $this->expectException(ValidationException::class);

        app(NuirMahasiswaWorkspaceService::class)->proposeGuideSeat(
            $submission,
            $this->mahasiswa,
            1,
            $this->dosenP2Only->id,
        );
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

    public function test_workspace_save_semua_field_nui_memberi_notifikasi_dan_timestamp(): void
    {
        $title = 'Judul penelitian simulasi uji';
        $novelty = implode(' ', array_fill(0, 15, 'novelty'));
        $urgency = implode(' ', array_fill(0, 15, 'urgency'));
        $impact = implode(' ', array_fill(0, 15, 'impact'));

        $component = Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class);

        foreach ([
            ['title', $title, 'Judul berhasil disimpan.', 'title_saved_at'],
            ['novelty', $novelty, 'Novelty berhasil disimpan.', 'novelty_saved_at'],
            ['urgency', $urgency, 'Urgency berhasil disimpan.', 'urgency_saved_at'],
            ['impact', $impact, 'Impact berhasil disimpan.', 'impact_saved_at'],
        ] as [$field, $value, $notificationTitle, $savedAtColumn]) {
            $component
                ->call('saveNuiField', $field, $value)
                ->assertHasNoErrors()
                ->assertNotified($notificationTitle)
                ->assertSee('Diperbarui');

            $submission = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();
            $this->assertNotNull($submission);
            $this->assertSame($value, $submission->{$field});
            $this->assertNotNull($submission->{$savedAtColumn});
        }

        $component
            ->assertSet('nuiFieldsFilled', true)
            ->assertSet('titleSaved', true)
            ->assertSet('nuiComplete', true)
            ->assertSee('Usulan Calon Pembimbing')
            ->assertSee('Calon Pembimbing 1');

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSet('titleField', $title)
            ->assertSet('noveltyField', $novelty)
            ->assertSet('urgencyField', $urgency)
            ->assertSet('impactField', $impact)
            ->assertSee($title, false)
            ->assertSee($novelty, false)
            ->assertSee($urgency, false)
            ->assertSee($impact, false);
    }

    public function test_workspace_save_nui_field_berhasil_tanpa_batas_minimum_kata(): void
    {
        NuirSubmission::factory()->titleSlot()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
        ]);

        // Fewer than the old minimum (12 words) — should now succeed
        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->call('saveNuiField', 'novelty', 'terlalu sedikit')
            ->assertHasNoErrors()
            ->assertNotified('Novelty berhasil disimpan.');

        $this->assertDatabaseHas('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'novelty' => 'terlalu sedikit',
        ]);
    }

    public function test_workspace_save_nui_field_gagal_jika_melebihi_batas_kata(): void
    {
        NuirSubmission::factory()->titleSlot()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
        ]);

        $overLimit = implode(' ', array_fill(0, 301, 'kata'));

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->call('saveNuiField', 'novelty', $overLimit)
            ->assertHasNoErrors()
            ->assertNotified('Novelty gagal disimpan');

        $this->assertDatabaseMissing('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'novelty' => $overLimit,
        ]);
    }

    public function test_workspace_save_pertama_membuat_submission_otomatis(): void
    {
        $this->assertFalse(
            NuirSubmission::where('user_id', $this->mahasiswa->id)->exists(),
        );

        $title = 'Judul penelitian simulasi uji';

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->call('saveNuiField', 'title', $title)
            ->assertHasNoErrors()
            ->assertSee('Diperbarui');

        $submission = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();

        $this->assertNotNull($submission);
        $this->assertSame('title_slot', $submission->status);
        $this->assertSame($title, $submission->title);
        $this->assertNotNull($submission->title_saved_at);
    }

    public function test_workspace_save_nui_field_menyimpan_nilai_dan_timestamp(): void
    {
        $submission = NuirSubmission::factory()->titleSlot()->withSavedTitle()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
        ]);

        $novelty = implode(' ', array_fill(0, 15, 'novelty'));

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->call('saveNuiField', 'novelty', $novelty)
            ->assertHasNoErrors()
            ->assertSee('Diperbarui')
            ->assertSee('Novelty (v1): tersimpan');

        $submission->refresh();

        $this->assertSame($novelty, $submission->novelty);
        $this->assertNotNull($submission->novelty_saved_at);
    }

    public function test_card_pembimbing_muncul_setelah_judul_disimpan(): void
    {
        NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => now(),
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSet('titleSaved', true)
            ->assertSet('nuiFieldsFilled', true)
            ->assertSee('Usulan Calon Pembimbing')
            ->assertSee('Komponen NUIR')
            ->assertSee('Calon Pembimbing 1')
            ->assertSee('Referensi');
    }

    public function test_card_pembimbing_tersembunyi_sebelum_judul_disimpan(): void
    {
        NuirSubmission::factory()->titleSlot()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => null,
            'novelty' => '',
            'urgency' => '',
            'impact' => '',
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSet('titleSaved', false)
            ->assertSet('nuiFieldsFilled', false)
            ->assertSee('Judul')
            ->assertDontSee('Usulan Calon Pembimbing')
            ->assertDontSee('Komponen NUIR')
            ->assertDontSee('Referensi #1');
    }

    public function test_card_lain_muncul_setelah_simpan_judul_pertama_kali(): void
    {
        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSet('titleSaved', false)
            ->assertDontSee('Usulan Calon Pembimbing')
            ->call('saveNuiField', 'title', 'Judul penelitian simulasi uji')
            ->assertHasNoErrors()
            ->assertSet('titleSaved', true)
            ->assertSee('Usulan Calon Pembimbing')
            ->assertSee('Komponen NUIR')
            ->assertSee('Referensi');
    }

    public function test_workspace_ajukan_pembimbing_dari_filament(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => now(),
            'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
            'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
            'impact' => implode(' ', array_fill(0, 15, 'impact')),
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->set('guide1Selection', $this->dosenP1->id)
            ->call('proposeGuide', 1)
            ->assertNotified('Usulan Pembimbing 1 dikirim.');

        $this->assertDatabaseHas('nuir_proposals', [
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide1_status' => 'pending',
        ]);
    }

    public function test_mahasiswa_dapat_membatalkan_usulan_pembimbing_yang_masih_menunggu(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => now(),
            'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
            'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
            'impact' => implode(' ', array_fill(0, 15, 'impact')),
        ]);

        $proposal = NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide1_status' => 'pending',
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSee('Batalkan Usulan')
            ->call('cancelGuide', 1)
            ->assertNotified('Usulan Pembimbing 1 dibatalkan.');

        $this->assertDatabaseHas('nuir_proposals', [
            'id' => $proposal->id,
            'guide1_id' => null,
            'guide1_status' => 'pending',
        ]);

        $this->assertDatabaseHas('nuir_revision_events', [
            'nuir_proposal_id' => $proposal->id,
            'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_CANCELLATION,
            'actor_role' => NuirRevisionEvent::ROLE_MAHASISWA,
            'actor_id' => $this->mahasiswa->id,
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSee('Dibatalkan Mahasiswa')
            ->assertDontSee('Dibatalkan Manajer');
    }

    public function test_mahasiswa_tidak_dapat_membatalkan_usulan_yang_sudah_diterima(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => now(),
            'novelty' => implode(' ', array_fill(0, 15, 'novelty')),
            'urgency' => implode(' ', array_fill(0, 15, 'urgency')),
            'impact' => implode(' ', array_fill(0, 15, 'impact')),
        ]);

        $proposal = NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide1_status' => 'accepted',
            'guide1_responded_at' => now(),
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertDontSee('Batalkan Usulan')
            ->call('cancelGuide', 1);

        $this->assertDatabaseHas('nuir_proposals', [
            'id' => $proposal->id,
            'guide1_id' => $this->dosenP1->id,
            'guide1_status' => 'accepted',
        ]);
    }

    public function test_card_dokumen_nuir_tersembunyi_hingga_ditrigger(): void
    {
        NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSee('Lampirkan dokumen NUIR (Google Drive)')
            ->assertDontSee('Simpan Link Dokumen')
            ->call('toggleDocumentCard')
            ->assertSee('Simpan Link Dokumen');
    }

    public function test_mahasiswa_dapat_menyimpan_link_dokumen_google_drive(): void
    {
        NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $rawLink = 'drive.google.com/file/d/abc123/view?usp=sharing';
        $expectedLink = 'https://drive.google.com/file/d/abc123/view?usp=sharing';

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->set('showDocumentCard', true)
            ->set('nuirDocumentLink', $rawLink)
            ->call('saveDocumentLink')
            ->assertNotified('Link dokumen NUIR berhasil disimpan.');

        $this->assertDatabaseHas('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'nuir_document_link' => $expectedLink,
        ]);
    }
}
