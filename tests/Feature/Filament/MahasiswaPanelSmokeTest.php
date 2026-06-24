<?php

namespace Tests\Feature\Filament;

use App\Filament\Dbs\Resources\NuirSettingResource;
use App\Filament\Mahasiswa\Pages\CreateNuirSubmission;
use App\Filament\Mahasiswa\Pages\Dashboard;
use App\Filament\Mahasiswa\Pages\EditNuirSubmission;
use App\Filament\Mahasiswa\Pages\NuirProposalOverview;
use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Models\GuideExaminer;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
    }

    public function test_dashboard_redirects_mahasiswa_to_filament_panel(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get('/dashboard')
            ->assertRedirect('/mahasiswa');
    }

    public function test_mahasiswa_dashboard_accessible(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertOk();
    }

    public function test_old_nuir_submission_route_redirects_to_filament(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get('/nuir/submission')
            ->assertRedirect(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'));
    }

    public function test_nuir_submission_overview_accessible(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk();
    }

    public function test_nuir_submission_create_accessible(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get(CreateNuirSubmission::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Tambah Referensi')
            ->assertSee('Simpan Referensi');
    }

    public function test_nuir_submission_edit_menampilkan_form_card_dan_modal_referensi(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $submission = NuirSubmission::factory()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
            'quote' => 'Kutipan contoh referensi',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get(EditNuirSubmission::getUrl(['record' => $submission], panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Tambah Referensi')
            ->assertSee('Kutipan contoh referensi')
            ->assertSee('data-nui-autoresize');
    }

    public function test_old_nuir_proposal_route_redirects_to_filament(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get('/nuir/proposal')
            ->assertRedirect(NuirProposalOverview::getUrl(panel: 'mahasiswa'));
    }

    public function test_nuir_proposal_overview_accessible(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get(NuirProposalOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk();
    }

    public function test_mahasiswa_cannot_access_dbs_panel(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(NuirSettingResource::getUrl('index', panel: 'dbs'))
            ->assertForbidden();
    }

    public function test_dbs_cannot_access_mahasiswa_panel(): void
    {
        $dbs = User::factory()->create()->assignRole('dbs');

        $this->actingAs($dbs)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertForbidden();
    }
}
