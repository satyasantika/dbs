<?php

namespace Tests\Feature\Filament;

use App\Filament\Mahasiswa\Pages\GuideQuotaRecap as MahasiswaGuideQuotaRecap;
use App\Filament\NuirManajer\Pages\GuideQuotaRecap as ManajerGuideQuotaRecap;
use App\Models\GuideExaminer;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\SelectionStage;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;

class GuideQuotaRecapTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_manajer_melihat_rekap_kuota_default_ke_angkatan_aktif(): void
    {
        $manajer = User::factory()->create()->assignRole('manajer nuir');
        $dosen = User::factory()->create(['name' => 'Dosen Rekap'])->assignRole('dosen');

        NuirSetting::factory()->create(['year_generation' => '2022', 'active' => true]);
        $allocation = $this->seedGuideAllocation($dosen, '2022', guide1Quota: 5, guide2Quota: 3);
        $allocation->update(['guide1_filled' => 2, 'guide2_filled' => 1]);

        $this->actingAs($manajer)
            ->get(ManajerGuideQuotaRecap::getUrl(panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Dosen Rekap')
            ->assertSee('2022', false);
    }

    public function test_mahasiswa_melihat_rekap_kuota_terkunci_pada_angkatannya(): void
    {
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($mahasiswa)->create(['year_generation' => '2023']);

        $dosen = User::factory()->create(['name' => 'Dosen Angkatan 2023'])->assignRole('dosen');
        $this->seedGuideAllocation($dosen, '2023', guide1Quota: 4);

        // Kuota angkatan lain — tidak boleh ikut tampil.
        $dosenLain = User::factory()->create(['name' => 'Dosen Angkatan 2022'])->assignRole('dosen');
        $this->seedGuideAllocation($dosenLain, '2022');

        $this->actingAs($mahasiswa)
            ->get(MahasiswaGuideQuotaRecap::getUrl(panel: 'mahasiswa'))
            ->assertOk()
            ->assertSee('Dosen Angkatan 2023')
            ->assertSee('2023', false)
            ->assertDontSee('Dosen Angkatan 2022');
    }

    public function test_tombol_pengesahan_muncul_hanya_jika_semua_mahasiswa_siap(): void
    {
        $manajer = User::factory()->create()->assignRole('manajer nuir');
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($mahasiswa)->create(['year_generation' => '2022']);
        NuirSubmission::factory()->submitted()->create(['user_id' => $mahasiswa->id, 'year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'active' => true]);

        Livewire::actingAs($manajer)
            ->test(ManajerGuideQuotaRecap::class)
            ->set('yearGeneration', '2022')
            ->assertActionHidden('ratify');

        SelectionStage::factory()->create(['user_id' => $mahasiswa->id, 'stage_order' => 1]);

        Livewire::actingAs($manajer)
            ->test(ManajerGuideQuotaRecap::class)
            ->set('yearGeneration', '2022')
            ->assertActionVisible('ratify')
            ->callAction('ratify')
            ->assertHasNoActionErrors();
    }
}
