<?php

namespace Tests\Unit;

use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Support\NuirMahasiswaFieldStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirMahasiswaFieldStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_field_ui_compose_when_belum_disimpan(): void
    {
        $submission = NuirSubmission::factory()->titleSlot()->create([
            'title' => '',
            'title_saved_at' => null,
        ]);

        $ui = NuirMahasiswaFieldStatus::workspaceFieldUi($submission, null, 'title', 'Judul');

        $this->assertSame('compose', $ui['action']);
        $this->assertFalse($ui['readonly']);
        $this->assertSame('Simpan Judul', $ui['saveLabel']);
    }

    public function test_workspace_field_ui_edit_setelah_judul_disimpan(): void
    {
        $submission = NuirSubmission::factory()->titleSlot()->create([
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => now(),
        ]);

        $ui = NuirMahasiswaFieldStatus::workspaceFieldUi($submission, null, 'title', 'Judul');

        $this->assertSame('edit', $ui['action']);
        $this->assertTrue($ui['readonly']);
        $this->assertTrue($ui['showEdit']);
        $this->assertSame('Edit Judul (v1)', $ui['editLabel']);
        $this->assertSame('v1', $ui['versionLabel']);
    }

    public function test_judul_menunggu_respon_saat_ada_proposal(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => now(),
        ]);

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
        ]);

        $status = NuirMahasiswaFieldStatus::titleFieldStatus($submission, $proposal);

        $this->assertSame(NuirMahasiswaFieldStatus::KEY_WAITING_RESPONSE, $status['key']);
        $this->assertSame('Judul (v1): menunggu respon', $status['label']);
        $this->assertSame('v1', $status['versionLabel']);
        $this->assertSame('info', $status['color']);
    }

    public function test_judul_diminta_revisi_saat_status_revision(): void
    {
        $submission = NuirSubmission::factory()->create([
            'status' => 'revision',
            'title' => 'Judul penelitian simulasi uji',
        ]);

        $status = NuirMahasiswaFieldStatus::titleFieldStatus($submission, null);

        $this->assertSame('Judul (v2): diminta revisi', $status['label']);
        $this->assertSame('danger', $status['color']);
        $this->assertSame('v2', $status['versionLabel']);
    }

    public function test_judul_disetujui_saat_content_ok(): void
    {
        $submission = NuirSubmission::factory()->contentOk()->create([
            'title' => 'Judul penelitian simulasi uji',
        ]);

        $status = NuirMahasiswaFieldStatus::titleFieldStatus($submission, null);

        $this->assertSame('Judul (v1): disetujui', $status['label']);
        $this->assertSame('v1', $status['versionLabel']);
        $this->assertSame('success', $status['color']);
    }

    public function test_novelty_menunggu_respon_sebelum_review_pembimbing(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'novelty' => str_repeat('novelty ', 15),
            'novelty_saved_at' => now(),
        ]);

        $guide1 = User::factory()->create();

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => null,
        ]);

        $submission->load('contentReviews');

        $status = NuirMahasiswaFieldStatus::nuiFieldStatus($submission, $proposal, 'novelty');

        $this->assertSame('Novelty (v1): menunggu respon', $status['label']);
        $this->assertSame('v1', $status['versionLabel']);
        $this->assertSame('info', $status['color']);
    }

    public function test_workspace_field_ui_edit_muncul_saat_menunggu_review_pembimbing(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'novelty' => str_repeat('novelty ', 15),
            'novelty_saved_at' => now(),
        ]);

        $guide1 = User::factory()->create();

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => null,
        ]);

        $submission->load('contentReviews');

        $ui = NuirMahasiswaFieldStatus::workspaceFieldUi($submission, $proposal, 'novelty', 'Novelty');

        $this->assertSame('edit', $ui['action']);
        $this->assertTrue($ui['showEdit']);
        $this->assertFalse($ui['canPersist']);
        $this->assertSame('Edit Novelty (v1)', $ui['editLabel']);
    }

    public function test_novelty_disetujui_kontekstual(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'novelty' => str_repeat('novelty ', 15),
            'novelty_saved_at' => now(),
        ]);

        $guide1 = User::factory()->create();
        $guide2 = User::factory()->create();

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
        ]);

        NuirContentReview::query()->create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $guide1->id,
            'field' => 'novelty',
            'role' => NuirContentReview::ROLE_GUIDE1,
            'approved' => true,
            'reviewed_at' => now(),
        ]);

        NuirContentReview::query()->create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $guide2->id,
            'field' => 'novelty',
            'role' => NuirContentReview::ROLE_GUIDE2,
            'approved' => true,
            'reviewed_at' => now(),
        ]);

        $submission->load('contentReviews');

        $status = NuirMahasiswaFieldStatus::nuiFieldStatus($submission, $proposal, 'novelty');

        $this->assertSame('Novelty (v1): disetujui', $status['label']);
        $this->assertSame('v1', $status['versionLabel']);
        $this->assertSame('success', $status['color']);
    }

    public function test_urgency_diminta_revisi_kontekstual(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'urgency' => str_repeat('urgency ', 15),
            'urgency_saved_at' => now(),
        ]);

        $guide1 = User::factory()->create();

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => null,
        ]);

        NuirContentReview::query()->create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $guide1->id,
            'field' => 'urgency',
            'role' => NuirContentReview::ROLE_GUIDE1,
            'approved' => false,
            'note' => 'Perlu diperjelas',
            'reviewed_at' => now(),
        ]);

        $submission->load('contentReviews');

        $status = NuirMahasiswaFieldStatus::nuiFieldStatus($submission, $proposal, 'urgency');

        $this->assertSame('Urgency (v2): diminta revisi', $status['label']);
        $this->assertSame('danger', $status['color']);
        $this->assertSame('v2', $status['versionLabel']);
    }

    public function test_workspace_field_ui_none_saat_nui_disetujui(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'novelty' => str_repeat('novelty ', 15),
            'novelty_saved_at' => now(),
        ]);

        $guide1 = User::factory()->create();
        $guide2 = User::factory()->create();

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
        ]);

        NuirContentReview::query()->create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $guide1->id,
            'field' => 'novelty',
            'role' => NuirContentReview::ROLE_GUIDE1,
            'approved' => true,
            'reviewed_at' => now(),
        ]);

        NuirContentReview::query()->create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $guide2->id,
            'field' => 'novelty',
            'role' => NuirContentReview::ROLE_GUIDE2,
            'approved' => true,
            'reviewed_at' => now(),
        ]);

        $submission->load('contentReviews');

        $ui = NuirMahasiswaFieldStatus::workspaceFieldUi($submission, $proposal, 'novelty', 'Novelty');

        $this->assertSame('none', $ui['action']);
        $this->assertTrue($ui['readonly']);
        $this->assertFalse($ui['showEdit']);
        $this->assertSame('', $ui['saveLabel']);
        $this->assertSame('v1', $ui['versionLabel']);
    }

    public function test_workspace_field_ui_revision_saat_diminta_revisi(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'urgency' => str_repeat('urgency ', 15),
            'urgency_saved_at' => now(),
        ]);

        $guide1 = User::factory()->create();

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => null,
        ]);

        NuirContentReview::query()->create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $guide1->id,
            'field' => 'urgency',
            'role' => NuirContentReview::ROLE_GUIDE1,
            'approved' => false,
            'note' => 'Perlu diperjelas',
            'reviewed_at' => now(),
        ]);

        $submission->load('contentReviews');

        $ui = NuirMahasiswaFieldStatus::workspaceFieldUi($submission, $proposal, 'urgency', 'Urgency');

        $this->assertSame('revision', $ui['action']);
        $this->assertFalse($ui['showEdit']);
        $this->assertSame('Buat Revisi Urgency (v2)', $ui['editLabel']);
        $this->assertSame('Simpan Revisi Urgency (v2)', $ui['saveLabel']);
        $this->assertSame('v2', $ui['versionLabel']);
    }

    public function test_badge_menampilkan_v3_saat_ada_dua_permintaan_revisi(): void
    {
        $guide1 = User::factory()->create();

        $submission = NuirSubmission::factory()->submitted()->create([
            'impact' => str_repeat('impact ', 15),
            'impact_saved_at' => now(),
        ]);

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => null,
        ]);

        NuirContentReview::query()->create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $guide1->id,
            'field' => 'impact',
            'role' => NuirContentReview::ROLE_GUIDE1,
            'approved' => false,
            'note' => 'Revisi pertama',
            'reviewed_at' => now()->subDay(),
        ]);

        app(\App\Services\NuirRevisionHistoryService::class)->logNuiRevision(
            $submission,
            $guide1,
            \App\Models\NuirRevisionEvent::ROLE_GUIDE1,
            'impact',
            'Revisi pertama',
        );

        app(\App\Services\NuirRevisionHistoryService::class)->logNuiRevision(
            $submission,
            $guide1,
            \App\Models\NuirRevisionEvent::ROLE_GUIDE1,
            'impact',
            'Revisi kedua',
        );

        $submission->load('contentReviews');

        $status = NuirMahasiswaFieldStatus::nuiFieldStatus($submission, $proposal, 'impact');

        $this->assertSame('Impact (v3): diminta revisi', $status['label']);
        $this->assertSame('v3', $status['versionLabel']);

        $ui = NuirMahasiswaFieldStatus::workspaceFieldUi($submission, $proposal, 'impact', 'Impact');

        $this->assertSame('Simpan Revisi Impact (v3)', $ui['saveLabel']);
        $this->assertSame('Buat Revisi Impact (v3)', $ui['editLabel']);
    }

    public function test_novelty_menampilkan_badge_versi_setelah_disimpan(): void
    {
        $submission = NuirSubmission::factory()->titleSlot()->create([
            'novelty' => str_repeat('novelty ', 15),
            'novelty_saved_at' => now(),
        ]);

        $status = NuirMahasiswaFieldStatus::nuiFieldStatus($submission, null, 'novelty');

        $this->assertTrue(NuirMahasiswaFieldStatus::isWorkflowBadge($status));
        $this->assertSame('Novelty (v1): tersimpan', $status['label']);
        $this->assertSame('v1', $status['versionLabel']);
        $this->assertSame('gray', $status['color']);
    }

    public function test_novelty_menunggu_respon_setelah_submission_disubmit(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'novelty' => str_repeat('novelty ', 15),
            'novelty_saved_at' => now(),
        ]);

        $status = NuirMahasiswaFieldStatus::nuiFieldStatus($submission, null, 'novelty');

        $this->assertSame('Novelty (v1): menunggu respon', $status['label']);
        $this->assertSame('info', $status['color']);
    }

    public function test_badge_v1_saat_belum_ada_revisi(): void
    {
        $submission = NuirSubmission::factory()->submitted()->create([
            'impact' => str_repeat('impact ', 15),
            'impact_saved_at' => now(),
        ]);

        $guide1 = User::factory()->create();

        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => null,
        ]);

        $submission->load('contentReviews');

        $status = NuirMahasiswaFieldStatus::nuiFieldStatus($submission, $proposal, 'impact');

        $this->assertSame('Impact (v1): menunggu respon', $status['label']);
        $this->assertSame('v1', $status['versionLabel']);

        $ui = NuirMahasiswaFieldStatus::workspaceFieldUi($submission, $proposal, 'impact', 'Impact');

        $this->assertSame('Edit Impact (v1)', $ui['editLabel']);
        $this->assertSame('Simpan Impact (v1)', $ui['saveLabel']);
    }
}
