<?php

namespace Tests\Unit;

use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirMahasiswaWorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NuirMahasiswaWorkspaceServiceTest extends TestCase
{
    use RefreshDatabase;

    private NuirMahasiswaWorkspaceService $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = app(NuirMahasiswaWorkspaceService::class);
    }

    public function test_missing_nui_field_labels_menghitung_field_kosong(): void
    {
        $submission = NuirSubmission::factory()->titleSlot()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Judul penelitian simulasi uji',
            'novelty' => '',
            'urgency' => '',
            'impact' => '',
        ]);

        $this->assertSame(['Novelty', 'Urgency', 'Impact'], $this->workspace->missingNuiFieldLabels($submission));
        $this->assertFalse($this->workspace->hasAllNuiFieldsFilled($submission));
    }

    public function test_pembimbing_button_disabled_hint_kosong_saat_semua_field_terisi(): void
    {
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => now(),
        ]);

        $this->assertTrue($this->workspace->hasAllNuiFieldsFilled($submission));
    }

    public function test_has_title_been_saved_mengikuti_title_saved_at(): void
    {
        $submission = NuirSubmission::factory()->titleSlot()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Judul penelitian simulasi uji',
            'title_saved_at' => null,
        ]);

        $this->assertFalse($this->workspace->hasTitleBeenSaved($submission));

        $submission->update(['title_saved_at' => now()]);

        $this->assertTrue($this->workspace->hasTitleBeenSaved($submission->fresh()));
    }

    public function test_is_reference_slot_filled_mengikuti_konten_referensi(): void
    {
        $reference = NuirReference::factory()->create([
            'link_ojs' => '',
            'indexer_name' => '',
            'link_index' => '',
            'link_drive' => '',
            'quote' => '',
            'relevance' => '',
        ]);

        $this->assertFalse($this->workspace->isReferenceSlotFilled($reference));

        $reference->update(['quote' => 'Kutipan referensi']);

        $this->assertTrue($this->workspace->isReferenceSlotFilled($reference->fresh()));
    }

    public function test_guide_seat_state_can_cancel_true_saat_belum_ada_elemen_disetujui_pembimbing(): void
    {
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create();
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_status' => 'pending',
        ]);

        $state = $this->workspace->guideSeatState($submission, $proposal, 1);

        $this->assertTrue($state['can_cancel']);
    }

    /** @dataProvider nuiFieldProvider */
    public function test_guide_seat_state_can_cancel_false_saat_salah_satu_elemen_disetujui_pembimbing(string $field): void
    {
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create();
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_status' => 'pending',
        ]);

        NuirContentReview::create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $proposal->guide1_id,
            'role' => NuirContentReview::ROLE_GUIDE1,
            'field' => $field,
            'approved' => true,
            'reviewed_at' => now(),
        ]);

        $state = $this->workspace->guideSeatState($submission->fresh(), $proposal->fresh(), 1);

        $this->assertFalse($state['can_cancel']);
    }

    public function test_guide_seat_state_can_cancel_tidak_terpengaruh_persetujuan_pembimbing_lain(): void
    {
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create();
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_status' => 'pending',
            'guide2_status' => 'pending',
        ]);

        NuirContentReview::create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $proposal->guide2_id,
            'role' => NuirContentReview::ROLE_GUIDE2,
            'field' => NuirContentReview::FIELD_NOVELTY,
            'approved' => true,
            'reviewed_at' => now(),
        ]);

        $state = $this->workspace->guideSeatState($submission->fresh(), $proposal->fresh(), 1);

        $this->assertTrue($state['can_cancel']);
    }

    public function test_cancel_guide_seat_ditolak_jika_pembimbing_sudah_setujui_salah_satu_elemen(): void
    {
        $mahasiswa = User::factory()->create();
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $mahasiswa->id,
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_status' => 'pending',
        ]);

        NuirContentReview::create([
            'nuir_submission_id' => $submission->id,
            'user_id' => $proposal->guide1_id,
            'role' => NuirContentReview::ROLE_GUIDE1,
            'field' => NuirContentReview::FIELD_TITLE,
            'approved' => true,
            'reviewed_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        $this->workspace->cancelGuideSeat($submission, $mahasiswa, 1);
    }

    /** @return list<list<string>> */
    public static function nuiFieldProvider(): array
    {
        return [
            [NuirContentReview::FIELD_TITLE],
            [NuirContentReview::FIELD_NOVELTY],
            [NuirContentReview::FIELD_URGENCY],
            [NuirContentReview::FIELD_IMPACT],
        ];
    }
}
