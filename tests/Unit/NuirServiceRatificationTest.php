<?php

namespace Tests\Unit;

use App\Models\GuideExaminer;
use App\Models\NuirSubmission;
use App\Models\SelectionStage;
use App\Models\User;
use App\Services\NuirService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirServiceRatificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_can_ratify_false_jika_belum_semua_mahasiswa_punya_selection_stage(): void
    {
        $mahasiswa1 = User::factory()->create()->assignRole('mahasiswa');
        $mahasiswa2 = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($mahasiswa1)->create(['year_generation' => '2022']);
        GuideExaminer::factory()->forStudent($mahasiswa2)->create(['year_generation' => '2022']);

        NuirSubmission::factory()->submitted()->create(['user_id' => $mahasiswa1->id, 'year_generation' => '2022']);
        NuirSubmission::factory()->submitted()->create(['user_id' => $mahasiswa2->id, 'year_generation' => '2022']);

        SelectionStage::factory()->create(['user_id' => $mahasiswa1->id, 'stage_order' => 1]);

        $this->assertFalse(app(NuirService::class)->canRatifySelectionStages('2022'));
    }

    public function test_can_ratify_true_jika_semua_mahasiswa_non_draft_sudah_punya_selection_stage(): void
    {
        $mahasiswa1 = User::factory()->create()->assignRole('mahasiswa');
        $mahasiswa2 = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($mahasiswa1)->create(['year_generation' => '2022']);
        GuideExaminer::factory()->forStudent($mahasiswa2)->create(['year_generation' => '2022']);

        NuirSubmission::factory()->submitted()->create(['user_id' => $mahasiswa1->id, 'year_generation' => '2022']);
        NuirSubmission::factory()->submitted()->create(['user_id' => $mahasiswa2->id, 'year_generation' => '2022']);

        SelectionStage::factory()->create(['user_id' => $mahasiswa1->id, 'stage_order' => 1]);
        SelectionStage::factory()->create(['user_id' => $mahasiswa2->id, 'stage_order' => 1]);

        $this->assertTrue(app(NuirService::class)->canRatifySelectionStages('2022'));
    }

    public function test_can_ratify_mengabaikan_submission_draft(): void
    {
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($mahasiswa)->create(['year_generation' => '2022']);
        NuirSubmission::factory()->create(['user_id' => $mahasiswa->id, 'year_generation' => '2022', 'status' => 'draft']);

        $this->assertFalse(app(NuirService::class)->canRatifySelectionStages('2022'));
    }

    public function test_ratify_selection_stages_menulis_ke_guide_examiners_dan_menandai_final(): void
    {
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $guide1 = User::factory()->create()->assignRole('dosen');
        $guide2 = User::factory()->create()->assignRole('dosen');
        $ge = GuideExaminer::factory()->forStudent($mahasiswa)
            ->create(['year_generation' => '2022', 'guide1_id' => null, 'guide2_id' => null]);

        $stage = SelectionStage::factory()->create([
            'user_id' => $mahasiswa->id,
            'stage_order' => 1,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
            'final' => false,
        ]);

        $promoted = app(NuirService::class)->ratifySelectionStages('2022');

        $this->assertSame(1, $promoted);
        $this->assertEquals($guide1->id, $ge->fresh()->guide1_id);
        $this->assertEquals($guide2->id, $ge->fresh()->guide2_id);
        $this->assertTrue($stage->fresh()->final);
    }

    public function test_ratify_selection_stages_tidak_memproses_ulang_yang_sudah_final(): void
    {
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($mahasiswa)->create(['year_generation' => '2022']);
        SelectionStage::factory()->final()->create(['user_id' => $mahasiswa->id, 'stage_order' => 1]);

        $promoted = app(NuirService::class)->ratifySelectionStages('2022');

        $this->assertSame(0, $promoted);
    }
}
