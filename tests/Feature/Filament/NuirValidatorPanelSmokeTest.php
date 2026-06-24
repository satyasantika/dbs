<?php

namespace Tests\Feature\Filament;

use App\Filament\NuirManajer\Resources\NuirSubmissionResource as ManajerSubmissionResource;
use App\Filament\NuirValidator\Pages\Dashboard as ValidatorDashboard;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource as ValidatorSubmissionResource;
use App\Models\GuideExaminer;
use App\Models\NuirAssignment;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirValidatorPanelSmokeTest extends TestCase
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

        NuirAssignment::create([
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_dashboard_redirects_validator_to_filament_panel(): void
    {
        $this->actingAs($this->validator)
            ->get('/dashboard')
            ->assertRedirect('/nuir-validator');
    }

    public function test_validator_dashboard_accessible(): void
    {
        $this->actingAs($this->validator)
            ->get(ValidatorDashboard::getUrl(panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Selamat datang')
            ->assertSee('Submission Ditugaskan')
            ->assertSee('Referensi Pending');
    }

    public function test_validator_dapat_akses_daftar_dan_detail_submission(): void
    {
        NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('index', panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee($this->submission->user->name);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Referensi');
    }

    public function test_validator_tidak_melihat_submission_yang_belum_didelegasikan(): void
    {
        $other = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'year_generation' => '2022',
            'title' => 'Submission Tanpa Delegasi',
        ]);

        $this->actingAs($this->validator);
        $visibleIds = ValidatorSubmissionResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($this->submission->id, $visibleIds);
        $this->assertNotContains($other->id, $visibleIds);
    }

    public function test_manajer_tidak_dapat_akses_panel_validator(): void
    {
        $this->actingAs($this->manajer)
            ->get(ValidatorSubmissionResource::getUrl('index', panel: 'nuir-validator'))
            ->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_akses_panel_validator(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(ValidatorDashboard::getUrl(panel: 'nuir-validator'))
            ->assertForbidden();
    }
}
