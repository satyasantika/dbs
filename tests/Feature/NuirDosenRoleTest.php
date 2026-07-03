<?php

namespace Tests\Feature;

use App\Models\GuideAllocation;
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

class NuirDosenRoleTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $mahasiswa;

    protected User $dosenP1;

    protected User $dosenP2;

    protected User $dosenP3;

    protected NuirSubmission $submission;

    protected NuirProposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosenP1 = User::factory()->create()->assignRole('dosen');
        $this->dosenP2 = User::factory()->create()->assignRole('dosen');
        $this->dosenP3 = User::factory()->create()->assignRole('dosen');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $this->seedGuideAllocation($this->dosenP1);
        $this->seedGuideAllocation($this->dosenP2);
        $this->seedGuideAllocation($this->dosenP3);
        $this->submission = NuirSubmission::factory()->contentOk()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
        $this->proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide2_id' => $this->dosenP2->id,
        ]);
        GuideAllocation::where('user_id', $this->dosenP1->id)->update(['guide1_filled' => 1]);
        GuideAllocation::where('user_id', $this->dosenP2->id)->update(['guide2_filled' => 1]);
    }

    public function test_dosen_dapat_setujui_atau_minta_revisi_per_elemen_nui(): void
    {
        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                'field' => 'novelty',
                'approved' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('nuir_content_reviews', [
            'nuir_submission_id' => $this->submission->id,
            'user_id' => $this->dosenP1->id,
            'field' => NuirContentReview::FIELD_NOVELTY,
            'approved' => true,
        ]);

        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                'field' => 'urgency',
                'approved' => '0',
                'note' => 'Urgency perlu diperjelas',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('nuir_content_reviews', [
            'user_id' => $this->dosenP1->id,
            'field' => NuirContentReview::FIELD_URGENCY,
            'approved' => false,
            'note' => 'Urgency perlu diperjelas',
        ]);
    }

    public function test_dosen_dapat_setujui_judul(): void
    {
        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                'field' => 'title',
                'approved' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('nuir_content_reviews', [
            'nuir_submission_id' => $this->submission->id,
            'user_id' => $this->dosenP1->id,
            'field' => NuirContentReview::FIELD_TITLE,
            'approved' => true,
        ]);
    }

    public function test_dosen_dapat_membatalkan_persetujuan_elemen_nui(): void
    {
        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                'field' => 'novelty',
                'approved' => '1',
            ]);

        $this->assertDatabaseHas('nuir_content_reviews', [
            'user_id' => $this->dosenP1->id,
            'field' => NuirContentReview::FIELD_NOVELTY,
            'approved' => true,
        ]);

        $this->actingAs($this->dosenP1)
            ->delete("/nuir/dosen/{$this->proposal->id}/content", ['field' => 'novelty'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('nuir_content_reviews', [
            'user_id' => $this->dosenP1->id,
            'field' => NuirContentReview::FIELD_NOVELTY,
        ]);
    }

    public function test_membatalkan_persetujuan_elemen_nui_mengembalikan_kursi_ke_pending(): void
    {
        $this->approveAllNuiFields($this->dosenP1, $this->proposal);
        $this->assertEquals('accepted', $this->proposal->fresh()->guide1_status);

        $this->actingAs($this->dosenP1)
            ->delete("/nuir/dosen/{$this->proposal->id}/content", ['field' => 'novelty']);

        $this->assertEquals('pending', $this->proposal->fresh()->guide1_status);
    }

    public function test_dosen_dapat_membatalkan_persetujuan_referensi(): void
    {
        $reference = \App\Models\NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$this->proposal->id}/references/{$reference->id}", [
                'approved' => '1',
            ]);

        $this->assertDatabaseHas('nuir_reference_reviews', [
            'nuir_reference_id' => $reference->id,
            'user_id' => $this->dosenP1->id,
            'approved' => true,
        ]);

        $this->actingAs($this->dosenP1)
            ->delete("/nuir/dosen/{$this->proposal->id}/references/{$reference->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('nuir_reference_reviews', [
            'nuir_reference_id' => $reference->id,
            'user_id' => $this->dosenP1->id,
        ]);
    }

    public function test_minta_revisi_wajib_catatan(): void
    {
        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                'field' => 'impact',
                'approved' => '0',
                'note' => '',
            ])
            ->assertSessionHasErrors('note');
    }

    public function test_tolak_usulan_nui_wajib_catatan(): void
    {
        $this->actingAs($this->dosenP2)
            ->put("/nuir/dosen/{$this->proposal->id}/reject", ['note' => ''])
            ->assertSessionHasErrors('note');
    }

    public function test_kursi_diterima_otomatis_setelah_semua_elemen_disetujui(): void
    {
        $this->approveAllNuiFields($this->dosenP1, $this->proposal);

        $this->assertEquals('accepted', $this->proposal->fresh()->guide1_status);
        $this->assertEquals('pending', $this->proposal->fresh()->guide2_status);
        $this->assertFalse($this->proposal->fresh()->final);
    }

    public function test_tidak_final_jika_satu_kursi_masih_minta_revisi(): void
    {
        $this->approveAllNuiFields($this->dosenP1, $this->proposal);

        $this->actingAs($this->dosenP2)
            ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                'field' => 'novelty',
                'approved' => '0',
                'note' => 'Novelty kurang spesifik',
            ]);

        $this->approveNuiField($this->dosenP2, $this->proposal, 'urgency');
        $this->approveNuiField($this->dosenP2, $this->proposal, 'impact');

        $proposal = $this->proposal->fresh();
        $this->assertEquals('accepted', $proposal->guide1_status);
        $this->assertEquals('pending', $proposal->guide2_status);
        $this->assertFalse($proposal->final);
    }

    public function test_kedua_kursi_accepted_jika_kedua_pembimbing_menyetujui_seluruh_elemen(): void
    {
        $this->approveAllNuiFields($this->dosenP1, $this->proposal);
        $this->approveAllNuiFields($this->dosenP2, $this->proposal);

        $proposal = $this->proposal->fresh();
        $this->assertFalse($proposal->final);
        $this->assertEquals('content_ok', $proposal->submission->status);
        $this->assertEquals('accepted', $proposal->guide1_status);
        $this->assertEquals('accepted', $proposal->guide2_status);
        $this->assertTrue($proposal->isBothAccepted());
    }

    public function test_minta_revisi_mengembalikan_kursi_ke_pending(): void
    {
        $this->approveAllNuiFields($this->dosenP1, $this->proposal);
        $this->assertEquals('accepted', $this->proposal->fresh()->guide1_status);

        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$this->proposal->id}/content", [
                'field' => 'impact',
                'approved' => '0',
                'note' => 'Impact perlu diperkuat',
            ]);

        $this->assertEquals('pending', $this->proposal->fresh()->guide1_status);
        $this->assertNull($this->proposal->fresh()->guide1_responded_at);
    }

    public function test_penolakan_satu_kursi_mengosongkan_kursi_dan_mengembalikan_kuota(): void
    {
        $this->approveAllNuiFields($this->dosenP1, $this->proposal);

        $this->actingAs($this->dosenP2)
            ->put("/nuir/dosen/{$this->proposal->id}/reject", [
                'note' => 'Tidak dapat membimbing topik ini',
            ])
            ->assertRedirect(route('nuir.dosen.index'));

        $proposal = $this->proposal->fresh();
        $this->assertEquals('accepted', $proposal->guide1_status);
        $this->assertEquals('rejected', $proposal->guide2_status);
        $this->assertEquals('Tidak dapat membimbing topik ini', $proposal->guide2_note);
        $this->assertFalse($proposal->final);

        $this->assertEquals(1, GuideAllocation::where('user_id', $this->dosenP1->id)->first()->fresh()->guide1_filled);
        $this->assertEquals(0, GuideAllocation::where('user_id', $this->dosenP2->id)->first()->fresh()->guide2_filled);
    }

    public function test_kursi_diterima_tetap_terkunci_saat_kursi_lain_ditolak(): void
    {
        $this->approveAllNuiFields($this->dosenP1, $this->proposal);

        $this->actingAs($this->dosenP2)
            ->put("/nuir/dosen/{$this->proposal->id}/reject", ['note' => 'Tidak bisa']);

        $locked = $this->submission->fresh()->lockedSeats();
        $this->assertEquals($this->dosenP1->id, $locked['guide1']['id']);
        $this->assertNull($locked['guide2']);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $this->submission->id,
                'guide1_id' => $this->dosenP1->id,
                'guide2_id' => $this->dosenP3->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));

        $newProposal = NuirProposal::where('nuir_submission_id', $this->submission->id)
            ->where('id', '!=', $this->proposal->id)
            ->first();

        $this->assertNotNull($newProposal);
        $this->assertEquals('accepted', $newProposal->guide1_status);
        $this->assertEquals('pending', $newProposal->guide2_status);
        $this->assertEquals($this->dosenP3->id, $newProposal->guide2_id);
    }

    public function test_tidak_dapat_konfirmasi_kursi_sebelum_semua_elemen_disetujui(): void
    {
        $this->approveNuiField($this->dosenP1, $this->proposal, 'novelty');
        $this->approveNuiField($this->dosenP1, $this->proposal, 'urgency');

        $this->actingAs($this->dosenP1)
            ->put("/nuir/dosen/{$this->proposal->id}/accept")
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertEquals('pending', $this->proposal->fresh()->guide1_status);
    }

    private function approveAllNuiFields(User $dosen, NuirProposal $proposal): void
    {
        foreach (NuirContentReview::FIELDS as $field) {
            $this->approveNuiField($dosen, $proposal, $field);
        }
    }

    private function approveNuiField(User $dosen, NuirProposal $proposal, string $field): void
    {
        $this->actingAs($dosen)
            ->patch("/nuir/dosen/{$proposal->id}/content", [
                'field' => $field,
                'approved' => '1',
            ]);
    }
}
