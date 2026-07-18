<?php

namespace Tests\Feature\Filament;

use App\Filament\Dbs\Pages\Dashboard;
use App\Filament\Dbs\Resources\NuirProposalResource;
use App\Filament\Dbs\Resources\NuirSettingResource;
use App\Filament\Dbs\Resources\NuirSubmissionResource;
use App\Filament\Resources\ExamRegistrationResource;
use App\Filament\Resources\GuideAllocationResource;
use App\Filament\Resources\GuideExaminerResource;
use App\Filament\Resources\GuideGroupResource;
use App\Filament\Resources\SelectionElementCommentResource;
use App\Filament\Resources\SelectionElementResource;
use App\Filament\Resources\SetScoringToExaminerResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DbsPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $dbs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->dbs = User::factory()->create()->assignRole('dbs');
    }

    public function test_dashboard_redirects_dbs_to_filament_panel(): void
    {
        $this->actingAs($this->dbs)
            ->get('/dashboard')
            ->assertRedirect('/dbs');
    }

    public function test_dbs_dashboard_accessible(): void
    {
        $this->actingAs($this->dbs)
            ->get(Dashboard::getUrl(panel: 'dbs'))
            ->assertOk();
    }

    public function test_nuir_settings_accessible(): void
    {
        $this->actingAs($this->dbs)
            ->get(NuirSettingResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(NuirSettingResource::getUrl('create', panel: 'dbs'))
            ->assertOk();
    }

    public function test_nuir_review_and_proposals_accessible(): void
    {
        $this->actingAs($this->dbs)
            ->get(NuirSubmissionResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(NuirProposalResource::getUrl('index', panel: 'dbs'))
            ->assertOk();
    }

    public function test_exam_and_selection_resources_accessible(): void
    {
        $this->actingAs($this->dbs)
            ->get(GuideAllocationResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(GuideGroupResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(SelectionElementResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(SelectionElementCommentResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(ExamRegistrationResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(GuideExaminerResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($this->dbs)
            ->get(SetScoringToExaminerResource::getUrl('index', panel: 'dbs'))
            ->assertOk();
    }

    public function test_dbs_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->dbs)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_mahasiswa_cannot_access_dbs_panel(): void
    {
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');

        $this->actingAs($mahasiswa)
            ->get(NuirSettingResource::getUrl('index', panel: 'dbs'))
            ->assertForbidden();
    }

    public function test_panel_dbs_menggunakan_sidebar_dan_nama_portal_dbs(): void
    {
        $panel = Filament::getPanel('dbs');

        $this->assertFalse($panel->hasTopNavigation());
        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertStringContainsString('Portal DBS', (string) $panel->getBrandName());

        $this->actingAs($this->dbs)
            ->get(Dashboard::getUrl(panel: 'dbs'))
            ->assertOk()
            ->assertSee('Portal DBS')
            ->assertDontSee('DBS Panel');
    }

    public function test_dbs_dengan_satu_role_tidak_melihat_ganti_peran(): void
    {
        $this->actingAs($this->dbs)
            ->get(Dashboard::getUrl(panel: 'dbs'))
            ->assertOk()
            ->assertDontSee('Ganti Peran');
    }

    public function test_dbs_dengan_role_ganda_melihat_ganti_peran(): void
    {
        $this->dbs->assignRole('dosen');

        $this->actingAs($this->dbs)
            ->get(Dashboard::getUrl(panel: 'dbs'))
            ->assertOk()
            ->assertSee('Ganti Peran');
    }

    public function test_panel_admin_tidak_terpengaruh_perubahan_ganti_peran(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertFalse($panel->hasTopNavigation());
        $this->assertSame('Portal Admin', $panel->getBrandName());
        $this->assertNull($panel->getHomeUrl());
    }
}
