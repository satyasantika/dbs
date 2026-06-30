<?php

namespace Tests\Feature;

use App\Filament\NuirManajer\Resources\GuideAllocationResource;
use App\Filament\NuirManajer\Resources\NuirSettingResource;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
use App\Models\GuideAllocation;
use App\Models\GuideExaminer;
use App\Models\NuirAssignment;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Support\NuirTextLimits;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;

class NuirManajerRoleTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $manajer;

    protected User $validator;

    protected User $mahasiswa;

    protected User $dosen;

    protected NuirSetting $setting;

    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->manajer = User::factory()->create()->assignRole('manajer nuir');
        $this->validator = User::factory()->create()->assignRole('validator nuir');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen = User::factory()->create()->assignRole('dosen');

        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        $this->setting = NuirSetting::factory()->create([
            'year_generation' => '2022',
            'stage' => 1,
            'active' => true,
            'min_references_approved' => 2,
            'max_references' => 5,
            'min_words_novelty' => 50,
            'max_words_novelty' => 300,
            'min_words_urgency' => 50,
            'max_words_urgency' => 300,
            'min_words_impact' => 50,
            'max_words_impact' => 300,
        ]);

        $this->submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('nuir-manajer'));
    }

    public function test_manajer_dapat_kelola_kuota_pembimbing_per_posisi(): void
    {
        $allocation = $this->seedGuideAllocation($this->dosen, guide1Quota: 3, guide2Quota: 4);

        $this->actingAs($this->manajer)
            ->get(GuideAllocationResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Kuota P1')
            ->assertSee('Kuota P2');

        Livewire::actingAs($this->manajer)
            ->test(GuideAllocationResource\Pages\EditGuideAllocation::class, [
                'record' => $allocation->getRouteKey(),
            ])
            ->fillForm([
                'guide1_quota' => 5,
                'guide2_quota' => 6,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $allocation->refresh();
        $this->assertEquals(5, $allocation->guide1_quota);
        $this->assertEquals(6, $allocation->guide2_quota);
    }

    public function test_manajer_dapat_mengatur_batas_kata_dan_referensi(): void
    {
        $this->actingAs($this->manajer)
            ->get(NuirSettingResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Konfigurasi NUIR');

        Livewire::actingAs($this->manajer)
            ->test(NuirSettingResource\Pages\EditNuirSettings::class, [
                'record' => $this->setting->getRouteKey(),
            ])
            ->fillForm([
                'min_words_novelty' => 80,
                'max_words_novelty' => 250,
                'min_words_urgency' => 70,
                'max_words_urgency' => 260,
                'min_words_impact' => 60,
                'max_words_impact' => 270,
                'min_references_approved' => 3,
                'max_references' => 8,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->setting->refresh();
        $this->assertEquals(80, $this->setting->min_words_novelty);
        $this->assertEquals(250, $this->setting->max_words_novelty);
        $this->assertEquals(3, $this->setting->min_references_approved);
        $this->assertEquals(8, $this->setting->max_references);
    }

    public function test_manajer_dapat_mendelegasikan_validator_dari_filament(): void
    {
        Livewire::actingAs($this->manajer)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->callInfolistAction('assignment.validator.name', 'manageValidator', data: [
                'validator_id' => $this->validator->id,
            ])
            ->assertHasNoInfolistActionErrors();

        $this->assertDatabaseHas('nuir_assignments', [
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
        ]);
    }

    public function test_manajer_dapat_mengubah_validator_yang_sudah_ditugaskan(): void
    {
        $validatorBaru = User::factory()->create()->assignRole('validator nuir');

        NuirAssignment::create([
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);

        Livewire::actingAs($this->manajer)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->assertSee('Ubah')
            ->callInfolistAction('assignment.validator.name', 'manageValidator', data: [
                'validator_id' => $validatorBaru->id,
            ])
            ->assertHasNoInfolistActionErrors();

        $this->assertDatabaseHas('nuir_assignments', [
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $validatorBaru->id,
        ]);
    }

    public function test_manajer_tidak_dapat_menyetujui_atau_minta_revisi_konten(): void
    {
        $this->assertFalse($this->manajer->can('review nuir submission'));

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-manajer'))
            ->assertOk()
            ->assertDontSee('Setujui Konten')
            ->assertDontSee('Minta Revisi');
    }

    public function test_detail_submission_menampilkan_jumlah_kata_konten(): void
    {
        $this->submission->update([
            'title' => 'Judul penelitian simulasi',
            'novelty' => str_repeat('novelty ', 80),
            'urgency' => str_repeat('urgency ', 70),
            'impact' => str_repeat('impact ', 60),
        ]);

        $this->setting->update([
            'min_words_novelty' => 50,
            'max_words_novelty' => 300,
            'min_words_urgency' => 50,
            'max_words_urgency' => 300,
            'min_words_impact' => 50,
            'max_words_impact' => 300,
        ]);

        NuirRevisionEvent::create([
            'nuir_submission_id' => $this->submission->id,
            'submission_version' => 1,
            'actor_id' => $this->dosen->id,
            'actor_role' => NuirRevisionEvent::ROLE_GUIDE1,
            'event_type' => NuirRevisionEvent::TYPE_NUI_REVISION,
            'subject' => 'novelty',
            'note' => 'Perjelas kebaruan penelitian.',
            'recorded_at' => now()->subDay(),
        ]);

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Judul')
            ->assertSee('Novelty')
            ->assertSee('Urgency')
            ->assertSee('Impact')
            ->assertSee('kata dikirim')
            ->assertSee('batas 50–300 kata')
            ->assertSee('Lihat histori revisi')
            ->assertSee('Revisi ke-2')
            ->assertSee('Referensi');
    }

    public function test_batas_kata_manajer_diterapkan_saat_mahasiswa_simpan_nuir(): void
    {
        $this->submission->delete();

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', [
                'title' => 'Judul',
                'novelty' => 'terlalu pendek',
                'urgency' => str_repeat('urgency ', 50),
                'impact' => str_repeat('impact ', 50),
            ])
            ->assertSessionHasErrors('novelty');
    }

    public function test_nuir_text_limits_menghitung_kata_dan_memvalidasi(): void
    {
        $this->assertEquals(5, NuirTextLimits::wordCount('satu dua tiga empat lima'));
        $this->assertNull(NuirTextLimits::validateNuiField(str_repeat('kata ', 100), $this->setting, 'novelty'));
        $this->assertNotNull(NuirTextLimits::validateNuiField('terlalu pendek', $this->setting, 'novelty'));
    }

    public function test_mahasiswa_tidak_dapat_akses_kelola_kuota_manajer(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(GuideAllocationResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertForbidden();
    }

    public function test_daftar_submission_menampilkan_tombol_dashboard_dan_progress_validasi(): void
    {
        NuirReference::factory()->count(3)->sequence(
            ['ref_order' => 1],
            ['ref_order' => 2],
            ['ref_order' => 3],
        )->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_approved' => null,
        ]);

        $inProgress = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'year_generation' => '2022',
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $inProgress->id,
            'ref_order' => 1,
            'ref_approved' => true,
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $inProgress->id,
            'ref_order' => 2,
            'ref_approved' => null,
        ]);

        $complete = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'year_generation' => '2022',
        ]);
        NuirReference::factory()->count(2)->sequence(
            ['ref_order' => 1, 'ref_approved' => true],
            ['ref_order' => 2, 'ref_approved' => false],
        )->create(['nuir_submission_id' => $complete->id]);

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Referensi Divalidasi')
            ->assertSee('Progress Validasi')
            ->assertSee('0/3')
            ->assertSee('1/2')
            ->assertSee('2/2')
            ->assertSee('Belum berprogress')
            ->assertSee('Berprogress')
            ->assertSee('Selesai');
    }
}
