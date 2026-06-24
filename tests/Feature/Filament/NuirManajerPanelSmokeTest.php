<?php

namespace Tests\Feature\Filament;

use App\Filament\NuirManajer\Pages\Dashboard as ManajerDashboard;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource as ManajerSubmissionResource;
use App\Models\GuideExaminer;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirManajerPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $manajer;

    private User $validator;

    private User $mahasiswa;

    private NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->manajer = User::factory()->create()->assignRole('manajer nuir');
        $this->validator = User::factory()->create()->assignRole('validator nuir');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');

        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
    }

    public function test_dashboard_redirects_manajer_to_filament_panel(): void
    {
        $this->actingAs($this->manajer)
            ->get('/dashboard')
            ->assertRedirect('/nuir-manajer');
    }

    public function test_manajer_dashboard_accessible(): void
    {
        $this->actingAs($this->manajer)
            ->get(ManajerDashboard::getUrl(panel: 'nuir-manajer'))
            ->assertOk();
    }

    public function test_manajer_dapat_akses_daftar_dan_detail_submission(): void
    {
        $this->actingAs($this->manajer)
            ->get(ManajerSubmissionResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee($this->submission->user->name);

        $this->actingAs($this->manajer)
            ->get(ManajerSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Delegasikan Validator')
            ->assertSee('Setujui Konten')
            ->assertSee('Minta Revisi');
    }

    public function test_manajer_tidak_melihat_submission_draft(): void
    {
        NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
            'title' => 'Draft Tersembunyi Manajer',
        ]);

        $this->actingAs($this->manajer)
            ->get(ManajerSubmissionResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertDontSee('Draft Tersembunyi Manajer');
    }

    public function test_validator_tidak_dapat_akses_panel_manajer(): void
    {
        $this->actingAs($this->validator)
            ->get(ManajerSubmissionResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_akses_panel_manajer(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(ManajerDashboard::getUrl(panel: 'nuir-manajer'))
            ->assertForbidden();
    }
}
