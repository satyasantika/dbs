<?php

namespace Tests\Feature\Filament;

use App\Filament\Dbs\Resources\NuirSettingResource;
use App\Filament\Mahasiswa\Pages\CreateNuirSubmission;
use App\Filament\Mahasiswa\Pages\Dashboard;
use App\Filament\Mahasiswa\Pages\EditNuirSubmission;
use App\Filament\Mahasiswa\Pages\MahasiswaEditProfile;
use App\Filament\Mahasiswa\Pages\NuirProposalOverview;
use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Models\GuideExaminer;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Selamat datang')
            ->assertSee('Portal mahasiswa')
            ->assertSee('Pengajuan NUIR')
            ->assertDontSee('Status Pengajuan NUIR');
    }

    public function test_dashboard_menampilkan_status_komponen_nui_terisi(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        NuirSubmission::factory()->titleSlot()->withSavedTitle()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul penelitian simulasi uji',
            'novelty' => implode(' ', array_fill(0, 12, 'novelty')),
            'novelty_saved_at' => now(),
            'urgency' => null,
            'impact' => null,
        ]);

        $this->actingAs($this->mahasiswa)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Status Pengajuan NUIR')
            ->assertSee('Judul (v1): tersimpan', false)
            ->assertSee('Novelty (v1): tersimpan', false)
            ->assertSee('Belum diisi');
    }

    public function test_card_status_pengajuan_nuir_muncul_begitu_slot_judul_dibuat(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        NuirSubmission::factory()->titleSlot()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'title' => 'Judul Slot',
            'title_saved_at' => now(),
        ]);

        $this->actingAs($this->mahasiswa)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Status Pengajuan NUIR');
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
            ->assertOk()
            ->assertSee('Judul')
            ->assertSee('Simpan Judul');
    }

    public function test_nuir_submission_create_redirects_to_workspace(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
            ->get(CreateNuirSubmission::getUrl(panel: 'mahasiswa'))
            ->assertRedirect(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'));
    }

    public function test_nuir_submission_workspace_menampilkan_referensi_tersimpan(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $submission = NuirSubmission::factory()->withNUI()->withSavedTitle()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'submitted',
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
            'quote' => 'Kutipan contoh referensi',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Referensi #1')
            ->assertSee('Kutipan contoh referensi')
            ->assertSee('Simpan Referensi #1');
    }

    public function test_workspace_menampilkan_status_referensi_per_slot(): void
    {
        NuirSetting::factory()->create([
            'year_generation' => '2022',
            'stage' => 1,
            'active' => true,
            'min_references_approved' => 2,
        ]);
        $submission = NuirSubmission::factory()->withNUI()->withSavedTitle()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'submitted',
        ]);
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
            'quote' => 'Referensi disetujui',
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 2,
            'indexer_name' => 'WoS',
            'quote' => 'Referensi menunggu review',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Disetujui Validator')
            ->assertSee('Menunggu Respon Validator');
    }

    public function test_submitted_submission_menampilkan_form_kelola_referensi(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $submission = NuirSubmission::factory()->submitted()->withNUI()->withSavedTitle()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
            'quote' => 'Referensi menunggu review validator',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Referensi')
            ->assertSee('Menunggu Respon Validator')
            ->assertDontSee('Kirim ke DBS');
    }

    public function test_overview_mengelompokkan_referensi_menurut_status_review(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $submission = NuirSubmission::factory()->withNUI()->withSavedTitle()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'submitted',
        ]);
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 2,
            'indexer_name' => 'WoS',
            'quote' => 'Menunggu',
        ]);
        NuirReference::factory()->rejected('Kurang relevan')->create([
            'nuir_submission_id' => $submission->id,
            'ref_order' => 3,
            'indexer_name' => 'DOAJ',
        ]);

        $this->actingAs($this->mahasiswa)
            ->get(NuirSubmissionOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Referensi #1')
            ->assertSee('Disetujui Validator')
            ->assertSee('Referensi #2')
            ->assertSee('Menunggu Respon Validator')
            ->assertSee('Referensi #3')
            ->assertSee('Diminta Revisi Validator')
            ->assertSee('Kurang relevan');
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

    public function test_panel_mahasiswa_menggunakan_sidebar_dan_menyediakan_profil(): void
    {
        $panel = Filament::getPanel('mahasiswa');

        $this->assertFalse($panel->hasTopNavigation());
        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertTrue($panel->hasProfile());
        $this->assertSame(MahasiswaEditProfile::class, $panel->getProfilePage());
        $this->assertStringContainsString('Portal Mahasiswa', (string) $panel->getBrandName());
    }

    public function test_mahasiswa_dengan_satu_role_tidak_melihat_ganti_peran(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertDontSee('Ganti Peran');
    }

    public function test_mahasiswa_dengan_role_ganda_melihat_ganti_peran(): void
    {
        $this->mahasiswa->assignRole('dbs');

        $this->actingAs($this->mahasiswa)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Ganti Peran');
    }

    public function test_mahasiswa_dapat_akses_halaman_ganti_password(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(MahasiswaEditProfile::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Ganti Password');
    }

    public function test_mahasiswa_dapat_mengganti_password(): void
    {
        Livewire::actingAs($this->mahasiswa)
            ->test(MahasiswaEditProfile::class)
            ->fillForm([
                'password' => 'password-baru-123',
                'passwordConfirmation' => 'password-baru-123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('password-baru-123', $this->mahasiswa->fresh()->password));
    }

    public function test_ganti_password_wajib_konfirmasi_yang_sama(): void
    {
        Livewire::actingAs($this->mahasiswa)
            ->test(MahasiswaEditProfile::class)
            ->fillForm([
                'password' => 'password-baru-123',
                'passwordConfirmation' => 'password-berbeda',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);
    }
}
