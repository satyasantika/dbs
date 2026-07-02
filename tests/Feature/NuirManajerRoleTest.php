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

    public function test_manajer_dapat_toggle_status_aktif_kuota_per_baris(): void
    {
        $allocation = $this->seedGuideAllocation($this->dosen, guide1Quota: 2, guide2Quota: 2);
        $this->assertTrue($allocation->active);

        Livewire::actingAs($this->manajer)
            ->test(GuideAllocationResource\Pages\ListGuideAllocations::class)
            ->callTableAction('toggleActive', $allocation);

        $allocation->refresh();
        $this->assertFalse($allocation->active);

        Livewire::actingAs($this->manajer)
            ->test(GuideAllocationResource\Pages\ListGuideAllocations::class)
            ->callTableAction('toggleActive', $allocation);

        $allocation->refresh();
        $this->assertTrue($allocation->active);
    }

    public function test_manajer_dapat_aktifkan_kuota_secara_bulk(): void
    {
        $inactive = $this->seedGuideAllocation($this->dosen, guide1Quota: 1, guide2Quota: 1);
        $inactive->update(['active' => false]);

        $otherDosen = User::factory()->create()->assignRole('dosen');
        $other = $this->seedGuideAllocation($otherDosen, guide1Quota: 1, guide2Quota: 1);
        $other->update(['active' => false]);

        Livewire::actingAs($this->manajer)
            ->test(GuideAllocationResource\Pages\ListGuideAllocations::class)
            ->callTableBulkAction('activate', [$inactive, $other]);

        $inactive->refresh();
        $other->refresh();
        $this->assertTrue($inactive->active);
        $this->assertTrue($other->active);
    }

    public function test_manajer_dapat_filter_kuota_berdasarkan_tahun(): void
    {
        $year2022 = $this->seedGuideAllocation($this->dosen, yearGeneration: '2022');
        $otherDosen = User::factory()->create()->assignRole('dosen');
        $year2099 = $this->seedGuideAllocation($otherDosen, yearGeneration: '2099');

        Livewire::actingAs($this->manajer)
            ->test(GuideAllocationResource\Pages\ListGuideAllocations::class)
            ->filterTable('year', '2099')
            ->assertCanSeeTableRecords([$year2099])
            ->assertCanNotSeeTableRecords([$year2022]);
    }

    public function test_manajer_dapat_menghapus_kuota_dari_tabel(): void
    {
        $allocation = $this->seedGuideAllocation($this->dosen, guide1Quota: 2, guide2Quota: 2);

        Livewire::actingAs($this->manajer)
            ->test(GuideAllocationResource\Pages\ListGuideAllocations::class)
            ->callTableAction('delete', $allocation);

        $this->assertDatabaseMissing('guide_allocations', [
            'id' => $allocation->id,
        ]);
    }

    public function test_manajer_dapat_menambah_konfigurasi_nuir(): void
    {
        $this->actingAs($this->manajer)
            ->get(NuirSettingResource::getUrl('create', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Angkatan');

        Livewire::actingAs($this->manajer)
            ->test(NuirSettingResource\Pages\CreateNuirSettings::class)
            ->fillForm([
                'year_generation' => '2030',
                'stage' => 1,
                'active' => true,
                'min_references_approved' => 5,
                'max_references' => 10,
                'min_words_novelty' => 50,
                'max_words_novelty' => 300,
                'min_words_urgency' => 50,
                'max_words_urgency' => 300,
                'min_words_impact' => 50,
                'max_words_impact' => 300,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('nuir_settings', [
            'year_generation' => '2030',
            'stage' => 1,
            'active' => true,
            'min_references_approved' => 5,
        ]);
    }

    public function test_manajer_dapat_mengatur_batas_kata_dan_referensi(): void
    {
        $this->actingAs($this->manajer)
            ->get(NuirSettingResource::getUrl('index', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Konfigurasi NUIR')
            ->assertSee('Tambah Data');

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

    public function test_manajer_dapat_menghapus_submission_nuir(): void
    {
        NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);
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
            ->assertActionVisible('deleteSubmission')
            ->callAction('deleteSubmission');

        $this->assertDatabaseMissing('nuir_submissions', ['id' => $this->submission->id]);
        $this->assertDatabaseMissing('nuir_references', ['nuir_submission_id' => $this->submission->id]);
        $this->assertDatabaseMissing('nuir_assignments', ['nuir_submission_id' => $this->submission->id]);
    }

    public function test_hapus_submission_melepaskan_kuota_pembimbing_yang_terpakai(): void
    {
        $allocation = $this->seedGuideAllocation($this->dosen, guide1Quota: 2, guide2Quota: 2);
        $allocation->update(['guide1_filled' => 1]);

        \App\Models\NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen->id,
            'guide1_status' => 'accepted',
            'guide1_responded_at' => now(),
        ]);

        Livewire::actingAs($this->manajer)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->callAction('deleteSubmission');

        $this->assertDatabaseMissing('nuir_submissions', ['id' => $this->submission->id]);
        $this->assertEquals(0, $allocation->fresh()->guide1_filled);
    }

    public function test_submission_finalized_tidak_dapat_dihapus(): void
    {
        $this->submission->update(['status' => 'finalized']);

        Livewire::actingAs($this->manajer)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->callAction('deleteSubmission');

        $this->assertDatabaseHas('nuir_submissions', ['id' => $this->submission->id]);
    }

    public function test_submission_dengan_versi_lebih_baru_tidak_dapat_dihapus(): void
    {
        // Versi lama dengan child non-draft otomatis tersembunyi dari resource manajer
        // (activeSubmissionsQuery), jadi guard ini diuji langsung lewat service.
        NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'parent_submission_id' => $this->submission->id,
            'version' => 2,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(\App\Services\NuirService::class)->deleteSubmission($this->submission, $this->manajer);
    }

    public function test_dosen_tidak_dapat_menghapus_submission_nuir(): void
    {
        $this->assertFalse($this->dosen->can('delete nuir submission'));
    }

    public function test_manajer_dapat_delegasikan_validator_via_select_inline_di_daftar(): void
    {
        Livewire::actingAs($this->manajer)
            ->test(NuirSubmissionResource\Pages\ListNuirSubmissions::class)
            ->call('updateTableColumnState', 'assignment.validator_id', $this->submission->getKey(), $this->validator->id);

        $this->assertDatabaseHas('nuir_assignments', [
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
        ]);
    }

    public function test_manajer_dapat_delegasikan_validator_untuk_beberapa_submission_sekaligus(): void
    {
        $submissionLain = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'year_generation' => '2022',
        ]);

        Livewire::actingAs($this->manajer)
            ->test(NuirSubmissionResource\Pages\ListNuirSubmissions::class)
            ->callTableBulkAction('delegateValidator', [$this->submission->getKey(), $submissionLain->getKey()], data: [
                'validator_id' => $this->validator->id,
            ]);

        $this->assertDatabaseHas('nuir_assignments', [
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
        ]);
        $this->assertDatabaseHas('nuir_assignments', [
            'nuir_submission_id' => $submissionLain->id,
            'validator_id' => $this->validator->id,
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

    public function test_view_submission_menampilkan_tombol_kembali_ke_daftar(): void
    {
        $indexUrl = NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer');

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Kembali ke Daftar Submission')
            ->assertSee($indexUrl, false);
    }

    public function test_ringkasan_submission_menampilkan_link_dokumen_nuir(): void
    {
        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Dokumen NUIR (Google Drive)', false)
            ->assertSee('Belum dilampirkan');

        $link = 'https://drive.google.com/file/d/xyz/view';

        $this->submission->update(['nuir_document_link' => $link]);

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Dokumen NUIR (Google Drive)', false)
            ->assertSee($link, false);
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
            ->assertDontSee('Revisi ke-1')
            ->assertSee('Referensi');
    }

    public function test_batas_kata_manajer_diterapkan_saat_mahasiswa_simpan_nuir(): void
    {
        $this->submission->delete();

        $this->actingAs($this->mahasiswa)
            ->post('/nuir/submission', [
                'title' => 'Judul',
                'novelty' => str_repeat('novelty ', 301),
                'urgency' => str_repeat('urgency ', 50),
                'impact' => str_repeat('impact ', 50),
            ])
            ->assertSessionHasErrors('novelty');
    }

    public function test_nuir_text_limits_menghitung_kata_dan_memvalidasi(): void
    {
        $this->assertEquals(5, NuirTextLimits::wordCount('satu dua tiga empat lima'));
        $this->assertNull(NuirTextLimits::validateNuiField(str_repeat('kata ', 100), $this->setting, 'novelty'));
        // min_words hanya petunjuk counter UI — tidak divalidasi saat simpan (lihat docs/nuir-simulasi.md).
        $this->assertNull(NuirTextLimits::validateNuiField('terlalu pendek', $this->setting, 'novelty'));
        $this->assertNotNull(NuirTextLimits::validateNuiField(str_repeat('kata ', 301), $this->setting, 'novelty'));
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
            ->assertSee(route('home'), false)
            ->assertSee('Referensi Divalidasi')
            ->assertSee('Progress Validasi')
            ->assertSee('0/3')
            ->assertSee('1/2')
            ->assertSee('2/2')
            ->assertSee('Belum berprogress')
            ->assertSee('Berprogress')
            ->assertSee('Selesai');
    }

    public function test_daftar_submission_terfilter_sesuai_kartu_dashboard(): void
    {
        $submittedUnassignedUser = User::factory()->create(['name' => 'Mhs Menunggu Delegasi'])->assignRole('mahasiswa');
        NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $submittedUnassignedUser->id,
            'year_generation' => '2022',
        ]);

        $submittedAssignedUser = User::factory()->create(['name' => 'Mhs Sudah Didelegasikan'])->assignRole('mahasiswa');
        $submittedAssigned = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $submittedAssignedUser->id,
            'year_generation' => '2022',
        ]);
        NuirAssignment::create([
            'nuir_submission_id' => $submittedAssigned->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);

        $revisionUser = User::factory()->create(['name' => 'Mhs Diminta Revisi'])->assignRole('mahasiswa');
        NuirSubmission::factory()->withNUI()->create([
            'user_id' => $revisionUser->id,
            'year_generation' => '2022',
            'status' => 'revision',
        ]);

        $contentOkUser = User::factory()->create(['name' => 'Mhs Konten Disetujui'])->assignRole('mahasiswa');
        NuirSubmission::factory()->withNUI()->create([
            'user_id' => $contentOkUser->id,
            'year_generation' => '2022',
            'status' => 'content_ok',
        ]);

        $supersededUser = User::factory()->create(['name' => 'Mhs Versi Lama'])->assignRole('mahasiswa');
        $superseded = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $supersededUser->id,
            'year_generation' => '2022',
        ]);
        $latestRevisionUser = User::factory()->create(['name' => 'Mhs Versi Terbaru'])->assignRole('mahasiswa');
        NuirSubmission::factory()->withNUI()->create([
            'user_id' => $latestRevisionUser->id,
            'year_generation' => '2022',
            'parent_submission_id' => $superseded->id,
            'version' => 2,
            'status' => 'revision',
        ]);

        $indexUrl = NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer');

        $this->actingAs($this->manajer)
            ->get($indexUrl)
            ->assertOk()
            ->assertSee('Mhs Menunggu Delegasi')
            ->assertSee('Mhs Sudah Didelegasikan')
            ->assertSee('Mhs Diminta Revisi')
            ->assertSee('Mhs Konten Disetujui')
            ->assertSee('Mhs Versi Terbaru')
            ->assertDontSee('Mhs Versi Lama');

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_UNASSIGNED))
            ->assertOk()
            ->assertSee('Belum Didelegasikan')
            ->assertSee('Dashboard')
            ->assertSee('Mhs Menunggu Delegasi')
            ->assertSee($this->submission->user->name)
            ->assertDontSee('Mhs Sudah Didelegasikan');

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_SUBMITTED))
            ->assertOk()
            ->assertSee('Menunggu Review')
            ->assertSee('Dashboard')
            ->assertSee('Mhs Menunggu Delegasi')
            ->assertSee('Mhs Sudah Didelegasikan')
            ->assertDontSee('Mhs Diminta Revisi')
            ->assertDontSee('Mhs Konten Disetujui');

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_REVISION))
            ->assertOk()
            ->assertSee('Diminta Revisi')
            ->assertSee('Dashboard')
            ->assertSee('Mhs Diminta Revisi')
            ->assertSee('Mhs Versi Terbaru')
            ->assertDontSee('Mhs Menunggu Delegasi')
            ->assertDontSee('Mhs Versi Lama');

        $this->actingAs($this->manajer)
            ->get(NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_CONTENT_OK))
            ->assertOk()
            ->assertSee('Konten Disetujui')
            ->assertSee('Dashboard')
            ->assertSee('Mhs Konten Disetujui')
            ->assertDontSee('Mhs Menunggu Delegasi')
            ->assertDontSee('Mhs Diminta Revisi');
    }

    public function test_manajer_dapat_akses_halaman_import_kuota(): void
    {
        $this->actingAs($this->manajer)
            ->get(GuideAllocationResource::getUrl('import', panel: 'nuir-manajer'))
            ->assertOk()
            ->assertSee('Cara menggunakan Import Banyak')
            ->assertSee('Referensi Urutan Kolom Spreadsheet');
    }

    public function test_manajer_dapat_import_kuota_via_copy_paste(): void
    {
        $this->dosen->update(['initial' => 'TST']);

        $this->actingAs($this->manajer)
            ->postJson(route('guideallocations.paste-import'), [
                'rows' => [[
                    '_rowNum' => 1,
                    'dosen' => 'TST',
                    'tahun' => '2022',
                    'kuota_p1' => '3',
                    'kuota_p2' => '4',
                    'aktif' => 'ya',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('guide_allocations', [
            'user_id' => $this->dosen->id,
            'year' => 2022,
            'guide1_quota' => 3,
            'guide2_quota' => 4,
            'active' => true,
        ]);
    }

    public function test_manajer_dapat_perbarui_kuota_yang_sudah_ada_via_import(): void
    {
        $this->dosen->update(['initial' => 'TST']);
        $allocation = $this->seedGuideAllocation($this->dosen, guide1Quota: 2, guide2Quota: 2);

        $this->actingAs($this->manajer)
            ->postJson(route('guideallocations.paste-import'), [
                'rows' => [[
                    '_rowNum' => 1,
                    'dosen' => 'TST',
                    'tahun' => '2022',
                    'kuota_p1' => '5',
                    'kuota_p2' => '6',
                    'aktif' => 'ya',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'success');

        $allocation->refresh();
        $this->assertEquals(5, $allocation->guide1_quota);
        $this->assertEquals(6, $allocation->guide2_quota);
    }

    public function test_manajer_dapat_import_kuota_menggunakan_username_dosen(): void
    {
        $this->actingAs($this->manajer)
            ->postJson(route('guideallocations.paste-import'), [
                'rows' => [[
                    '_rowNum' => 1,
                    'dosen' => $this->dosen->username,
                    'tahun' => '2023',
                    'kuota_p1' => '2',
                    'kuota_p2' => '3',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('guide_allocations', [
            'user_id' => $this->dosen->id,
            'year' => 2023,
            'guide1_quota' => 2,
            'guide2_quota' => 3,
        ]);
    }

    public function test_mahasiswa_tidak_dapat_import_kuota(): void
    {
        $this->actingAs($this->mahasiswa)
            ->postJson(route('guideallocations.paste-import'), [
                'rows' => [[
                    '_rowNum' => 1,
                    'dosen' => 'TST',
                    'tahun' => '2022',
                    'kuota_p1' => '1',
                    'kuota_p2' => '1',
                ]],
            ])
            ->assertForbidden();
    }
}
