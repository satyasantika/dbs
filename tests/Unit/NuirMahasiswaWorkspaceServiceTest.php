<?php

namespace Tests\Unit;

use App\Models\NuirReference;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirMahasiswaWorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
