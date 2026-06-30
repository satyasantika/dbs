<?php

namespace Tests\Feature;

use App\Models\GuideAllocation;
use App\Models\GuideExaminer;
use App\Models\NuirAssignment;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirAssignmentService;
use App\Services\NuirProposalService;
use App\Services\NuirRevisionHistoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;
use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;

class NuirSistemTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $mahasiswa;

    protected User $dosenP1;

    protected User $dosenP2;

    protected User $dosenP3;

    protected User $validator;

    protected User $manajer;

    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosenP1 = User::factory()->create()->assignRole('dosen');
        $this->dosenP2 = User::factory()->create()->assignRole('dosen');
        $this->dosenP3 = User::factory()->create()->assignRole('dosen');
        $this->validator = User::factory()->create()->assignRole('validator nuir');
        $this->manajer = User::factory()->create()->assignRole('manajer nuir');

        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->seedGuideAllocation($this->dosenP1);
        $this->seedGuideAllocation($this->dosenP2);
        $this->seedGuideAllocation($this->dosenP3);

        $this->submission = NuirSubmission::factory()->contentOk()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'version' => 1,
        ]);

        NuirAssignment::create([
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_histori_revisi_nui_tersimpan_dan_tetap_ada_setelah_mahasiswa_memperbaiki(): void
    {
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide2_id' => $this->dosenP2->id,
        ]);

        $this->actingAs($this->dosenP1)
            ->patch("/nuir/dosen/{$proposal->id}/content", [
                'field' => 'novelty',
                'approved' => '0',
                'note' => 'Novelty perlu diperjelas',
            ]);

        $this->assertDatabaseHas('nuir_revision_events', [
            'nuir_submission_id' => $this->submission->id,
            'event_type' => NuirRevisionEvent::TYPE_NUI_REVISION,
            'subject' => 'novelty',
            'note' => 'Novelty perlu diperjelas',
        ]);

        $this->actingAs($this->mahasiswa)
            ->put("/nuir/submission/{$this->submission->id}", [
                'novelty' => str_repeat('n', 120),
            ]);

        $this->assertDatabaseHas('nuir_revision_events', [
            'nuir_submission_id' => $this->submission->id,
            'subject' => 'novelty',
            'note' => 'Novelty perlu diperjelas',
        ]);
    }

    public function test_histori_revisi_referensi_tersimpan_setelah_mahasiswa_memperbaiki(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator(
            $ref,
            $this->validator,
            false,
            'Link index tidak dapat diakses',
            ['link_index'],
        );

        $ref->update([
            'link_index' => 'https://www.scopus.com/record/baru',
            'ref_approved' => null,
            'ref_note' => null,
            'ref_revision_fields' => null,
        ]);

        $this->assertDatabaseHas('nuir_revision_events', [
            'nuir_submission_id' => $this->submission->id,
            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
            'ref_order' => 1,
            'note' => 'Link index tidak dapat diakses',
        ]);

        $event = NuirRevisionEvent::query()->where('ref_order', 1)->first();
        $this->assertEquals(['link_index'], $event->revision_fields);
    }

    public function test_histori_revisi_antarversi_mencakup_versi_induk(): void
    {
        $v2 = NuirSubmission::factory()->contentOk()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'version' => 2,
            'parent_submission_id' => $this->submission->id,
        ]);

        NuirRevisionEvent::create([
            'nuir_submission_id' => $this->submission->id,
            'submission_version' => 1,
            'actor_id' => $this->validator->id,
            'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
            'subject' => '1',
            'ref_order' => 1,
            'note' => 'Catatan versi 1',
            'recorded_at' => now()->subDay(),
        ]);

        $history = app(NuirRevisionHistoryService::class)->historyForLineage($v2);

        $this->assertTrue($history->contains(fn ($event) => $event->note === 'Catatan versi 1'));
    }

    public function test_histori_penolakan_usulan_tersimpan_untuk_mahasiswa_dan_dosen(): void
    {
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosenP1->id,
            'guide2_id' => $this->dosenP2->id,
        ]);

        GuideAllocation::where('user_id', $this->dosenP2->id)->update(['guide2_filled' => 1]);

        $this->actingAs($this->dosenP2)
            ->put("/nuir/dosen/{$proposal->id}/reject", ['note' => 'Topik di luar keahlian']);

        $this->assertDatabaseHas('nuir_revision_events', [
            'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_REJECTION,
            'subject' => 'guide2',
            'note' => 'Topik di luar keahlian',
        ]);

        Livewire::actingAs($this->mahasiswa)
            ->test(NuirSubmissionOverview::class)
            ->assertSee('Histori Revisi')
            ->assertSee('Topik di luar keahlian');

        $this->actingAs($this->dosenP1)
            ->get("/nuir/dosen/{$proposal->id}")
            ->assertOk()
            ->assertSee('Histori Penolakan Usulan')
            ->assertSee('Topik di luar keahlian');
    }

    public function test_filter_pembimbing_per_posisi_kuota_dan_pengembalian_saat_penolakan(): void
    {
        GuideAllocation::where('user_id', $this->dosenP3->id)->update([
            'guide1_quota' => 0,
            'guide2_quota' => 2,
            'guide1_filled' => 0,
            'guide2_filled' => 0,
        ]);

        $service = app(NuirProposalService::class);
        $lockedSeats = $this->submission->lockedSeats();

        $p1Ids = $service->lecturersForSeat($this->mahasiswa, '2022', 1, $lockedSeats)->pluck('id')->all();
        $p2Ids = $service->lecturersForSeat($this->mahasiswa, '2022', 2, $lockedSeats)->pluck('id')->all();

        $this->assertContains($this->dosenP1->id, $p1Ids);
        $this->assertNotContains($this->dosenP3->id, $p1Ids);
        $this->assertContains($this->dosenP3->id, $p2Ids);

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/proposal', [
                'nuir_submission_id' => $this->submission->id,
                'guide1_id' => $this->dosenP1->id,
                'guide2_id' => $this->dosenP2->id,
            ])
            ->assertRedirect(route('nuir.proposal.index'));

        $this->assertEquals(1, GuideAllocation::where('user_id', $this->dosenP1->id)->first()->fresh()->guide1_filled);
        $this->assertEquals(1, GuideAllocation::where('user_id', $this->dosenP2->id)->first()->fresh()->guide2_filled);

        $proposal = NuirProposal::where('nuir_submission_id', $this->submission->id)->first();

        $this->actingAs($this->dosenP2)
            ->put("/nuir/dosen/{$proposal->id}/reject", ['note' => 'Tidak bisa']);

        $this->assertEquals(1, GuideAllocation::where('user_id', $this->dosenP1->id)->first()->fresh()->guide1_filled);
        $this->assertEquals(0, GuideAllocation::where('user_id', $this->dosenP2->id)->first()->fresh()->guide2_filled);
    }
}
