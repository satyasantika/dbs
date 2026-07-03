<?php

namespace Tests\Feature\Filament;

use App\Filament\Dosen\Pages\ChiefExam;
use App\Filament\Dosen\Pages\Dashboard as DosenDashboard;
use App\Filament\NuirManajer\Pages\Dashboard as NuirManajerDashboard;
use App\Filament\NuirValidator\Pages\Dashboard as NuirValidatorDashboard;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenNuirDashboardLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_dosen_dengan_role_validator_melihat_tautan_panel_nuir_validator(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'validator nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Validasi NUIR')
            ->assertSee('nuir-validator', false)
            ->assertDontSee('Manajemen NUIR');
    }

    public function test_dosen_dengan_role_manajer_melihat_tautan_panel_nuir_manajer(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'manajer nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Manajemen NUIR')
            ->assertSee('nuir-manajer', false)
            ->assertDontSee('Validasi NUIR');
    }

    public function test_dosen_tanpa_role_nuir_tidak_melihat_tautan_panel_nuir(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertDontSee('Validasi NUIR')
            ->assertDontSee('Manajemen NUIR');
    }

    public function test_dosen_melihat_menu_navigasi_panel(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Dashboard Dosen')
            ->assertSee('Ujian Belum Selesai Dinilai')
            ->assertSee('Arsip Penilaian')
            ->assertSee('Halaman Ketua Penguji')
            ->assertSee('Bimbingan Belum Lulus')
            ->assertSee('Lulusan Pembimbing Penguji');
    }

    public function test_panel_dosen_menggunakan_sidebar_dan_nama_portal_dosen(): void
    {
        $panel = Filament::getPanel('dosen');

        $this->assertFalse($panel->hasTopNavigation());
        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertStringContainsString('Portal Dosen', (string) $panel->getBrandName());

        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Portal Dosen')
            ->assertDontSee('DBS Penguji');
    }

    public function test_fitur_dosen_dibungkus_dalam_menu_dosen(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Menu Dosen');
    }

    public function test_menu_usulan_nuir_tampil_untuk_semua_dosen(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Menu usulan NUIR')
            ->assertSee(route('nuir.dosen.index'), false);
    }

    public function test_dosen_melihat_header_bimbingan_ujian_dan_review_usulan_nuir(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Bimbingan dan Ujian')
            ->assertSee('Review Usulan NUIR')
            ->assertSee('Usulan Menunggu Respons')
            ->assertSee('Perlu Review Judul/NUI');
    }

    public function test_review_usulan_nuir_menampilkan_jumlah_usulan_pending(): void
    {
        $dosen = User::factory()->create()->assignRole('dosen');
        $dosen->givePermissionTo('active');

        NuirProposal::factory()->create([
            'guide1_id' => $dosen->id,
            'guide1_status' => 'pending',
        ]);

        $this->actingAs($dosen)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Usulan Menunggu Respons')
            ->assertSeeInOrder(['Usulan Menunggu Respons', '1']);
    }

    public function test_review_usulan_nuir_menampilkan_jumlah_perlu_review_nui(): void
    {
        $dosen = User::factory()->create()->assignRole('dosen');
        $dosen->givePermissionTo('active');

        $submission = NuirSubmission::factory()->contentOk()->create();
        NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $dosen->id,
            'guide1_status' => 'pending',
        ]);

        $this->actingAs($dosen)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSeeInOrder(['Perlu Review Judul/NUI', '1']);
    }

    public function test_dosen_dengan_role_manajer_melihat_select_role_di_navbar(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'manajer nuir']);
        $user->givePermissionTo('active');

        // Diuji di halaman non-dashboard untuk memastikan select role muncul
        // di navbar semua halaman panel, bukan hanya lewat widget dashboard.
        $this->actingAs($user)
            ->get(ChiefExam::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('id="role-switcher"', false)
            ->assertSee(NuirManajerDashboard::getUrl(panel: 'nuir-manajer'), false)
            ->assertSee('Portal Manajer NUIR');
    }

    public function test_dosen_dengan_role_validator_melihat_select_role_di_navbar(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'validator nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(ChiefExam::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('id="role-switcher"', false)
            ->assertSee(NuirValidatorDashboard::getUrl(panel: 'nuir-validator'), false)
            ->assertSee('Portal Validator NUIR');
    }

    public function test_dosen_dengan_satu_role_tidak_melihat_select_role(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(ChiefExam::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertDontSee('id="role-switcher"', false);
    }

    public function test_dosen_dengan_ketiga_role_melihat_semua_opsi_select_role(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'manajer nuir', 'validator nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('id="role-switcher"', false)
            ->assertSeeInOrder(['Portal Dosen', 'Portal Manajer NUIR', 'Portal Validator NUIR']);
    }

    public function test_dosen_dengan_kedua_role_nuir_melihat_kedua_tautan(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'validator nuir', 'manajer nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Validasi NUIR')
            ->assertSee('Manajemen NUIR')
            ->assertSee('nuir-validator', false)
            ->assertSee('nuir-manajer', false);
    }
}
