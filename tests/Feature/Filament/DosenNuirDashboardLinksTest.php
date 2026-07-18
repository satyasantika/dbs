<?php

namespace Tests\Feature\Filament;

use App\Filament\Dosen\Pages\ChiefExam;
use App\Filament\Dosen\Pages\Dashboard as DosenDashboard;
use App\Filament\Dosen\Resources\NuirSubmissionResource;
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
            ->assertSee(NuirSubmissionResource::getUrl('index', panel: 'dosen'), false);
    }

    public function test_halaman_lama_nuir_dosen_diarahkan_ke_filament(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get('/nuir/dosen')
            ->assertRedirect(NuirSubmissionResource::getUrl('index', panel: 'dosen'));
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

    public function test_dosen_dengan_role_manajer_melihat_ganti_peran_dan_opsi_role_gate(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'manajer nuir']);
        $user->givePermissionTo('active');

        // Diuji di halaman non-dashboard untuk memastikan Ganti Peran muncul
        // di sidebar semua halaman panel, bukan hanya lewat widget dashboard.
        $this->actingAs($user)
            ->get(ChiefExam::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Ganti Peran');

        $this->actingAs($user)
            ->get(route('role.gate'))
            ->assertOk()
            ->assertSee(NuirManajerDashboard::getUrl(panel: 'nuir-manajer'), false)
            ->assertSee('Portal Manajer NUIR');
    }

    public function test_dosen_dengan_role_validator_melihat_ganti_peran_dan_opsi_role_gate(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'validator nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(ChiefExam::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Ganti Peran');

        $this->actingAs($user)
            ->get(route('role.gate'))
            ->assertOk()
            ->assertSee(NuirValidatorDashboard::getUrl(panel: 'nuir-validator'), false)
            ->assertSee('Portal Validator NUIR');
    }

    public function test_dosen_dengan_satu_role_tidak_melihat_ganti_peran(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(ChiefExam::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertDontSee('Ganti Peran');
    }

    public function test_dosen_dengan_ketiga_role_melihat_semua_opsi_role_gate(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'manajer nuir', 'validator nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk()
            ->assertSee('Ganti Peran');

        $this->actingAs($user)
            ->get(route('role.gate'))
            ->assertOk()
            ->assertSeeInOrder(['Portal Dosen', 'Portal Manajer NUIR', 'Portal Validator NUIR']);
    }

}
