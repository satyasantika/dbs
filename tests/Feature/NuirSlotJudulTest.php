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

class NuirSlotJudulTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $mahasiswa;

    protected User $dosen1;

    protected User $dosen2;

    protected User $dosen3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        $this->dosen3 = User::factory()->create()->assignRole('dosen');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $this->seedGuideAllocation($this->dosen1);
        $this->seedGuideAllocation($this->dosen2);
        $this->seedGuideAllocation($this->dosen3);
    }

    public function test_mahasiswa_buat_slot_judul_dulu_lalu_isi_nuir(): void
    {
        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', [
                'title' => 'Judul Slot',
                'title_only' => '1',
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $slot = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();
        $this->assertEquals('title_slot', $slot->status);
        $this->assertEquals('Judul Slot', $slot->title);
        $this->assertNull($slot->novelty);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$slot->id}", [
                'title' => 'Judul Slot',
                'novelty' => str_repeat('a', 100),
                'urgency' => str_repeat('b', 100),
                'impact' => str_repeat('c', 100),
            ])
            ->assertRedirect(route('nuir.submission.index'));

        $slot->refresh();
        $this->assertEquals('draft', $slot->status);
        $this->assertNotNull($slot->novelty);
    }

    public function test_proposal_mengonsumsi_kuota_p1_dan_p2_terpisah(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));

        $alloc1 = GuideAllocation::where('user_id', $this->dosen1->id)->first();
        $alloc2 = GuideAllocation::where('user_id', $this->dosen2->id)->first();
        $this->assertEquals(1, $alloc1->fresh()->guide1_filled);
        $this->assertEquals(0, $alloc1->fresh()->guide2_filled);
        $this->assertEquals(0, $alloc2->fresh()->guide1_filled);
        $this->assertEquals(1, $alloc2->fresh()->guide2_filled);
    }

    public function test_tolak_pembimbing_mengembalikan_kuota_sesuai_posisi(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        GuideAllocation::where('user_id', $this->dosen1->id)->update(['guide1_filled' => 1]);
        GuideAllocation::where('user_id', $this->dosen2->id)->update(['guide2_filled' => 1]);

        $this->actingAs($this->dosen2)
            ->put("/nuir/dosen/{$proposal->id}/reject", ['note' => 'tidak sesuai'])
            ->assertRedirect();

        $this->assertEquals(1, GuideAllocation::where('user_id', $this->dosen1->id)->first()->fresh()->guide1_filled);
        $this->assertEquals(0, GuideAllocation::where('user_id', $this->dosen2->id)->first()->fresh()->guide2_filled);
    }

    public function test_slot_sama_dipakai_ulang_kursi_kosong_dengan_pembimbing_terkunci(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $rejected = NuirProposal::factory()->guide1Accepted()->guide2Rejected('tidak bisa')->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
            'guide1_responded_at' => now(),
            'guide2_responded_at' => now(),
        ]);

        GuideAllocation::where('user_id', $this->dosen1->id)->update(['guide1_filled' => 1]);
        GuideAllocation::where('user_id', $this->dosen2->id)->update(['guide2_filled' => 0]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen3->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));

        $newProposal = NuirProposal::where('nuir_submission_id', $submission->id)
            ->where('id', '!=', $rejected->id)
            ->first();

        $this->assertNotNull($newProposal);
        $this->assertEquals('accepted', $newProposal->guide1_status);
        $this->assertEquals('pending', $newProposal->guide2_status);
        $this->assertEquals($this->dosen3->id, $newProposal->guide2_id);
        $this->assertEquals(1, GuideAllocation::where('user_id', $this->dosen1->id)->first()->fresh()->guide1_filled);
        $this->assertEquals(1, GuideAllocation::where('user_id', $this->dosen3->id)->first()->fresh()->guide2_filled);
    }

    public function test_pembimbing_dapat_review_nui_per_aspek(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->dosen1)
            ->patch("/nuir/dosen/{$proposal->id}/content", [
                'field' => 'novelty',
                'approved' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nuir_content_reviews', [
            'nuir_submission_id' => $submission->id,
            'user_id' => $this->dosen1->id,
            'field' => NuirContentReview::FIELD_NOVELTY,
            'role' => NuirContentReview::ROLE_GUIDE1,
            'approved' => true,
        ]);
    }

    public function test_proposal_ditolak_jika_kuota_posisi_habis(): void
    {
        GuideAllocation::where('user_id', $this->dosen1->id)->update([
            'guide1_quota' => 1,
            'guide1_filled' => 1,
        ]);

        $submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->dosen1->id,
                'guide2_id' => $this->dosen2->id,
            ])
            ->assertSessionHasErrors('guide1_id');
    }
}
