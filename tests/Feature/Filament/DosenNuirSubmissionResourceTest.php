<?php

namespace Tests\Feature\Filament;

use App\Filament\Dosen\Resources\NuirSubmissionResource;
use App\Models\GuideExaminer;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsNuirGuideQuota;
use Tests\TestCase;

class DosenNuirSubmissionResourceTest extends TestCase
{
    use RefreshDatabase;
    use SeedsNuirGuideQuota;

    protected User $guide1;

    protected User $guide2;

    protected User $mahasiswa;

    protected NuirSubmission $submission;

    protected NuirProposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->guide1 = User::factory()->create()->assignRole('dosen');
        $this->guide2 = User::factory()->create()->assignRole('dosen');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');

        $this->seedGuideAllocation($this->guide1);
        $this->seedGuideAllocation($this->guide2);

        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->guide1->id,
            'guide2_id' => $this->guide2->id,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('dosen'));
    }

    public function test_daftar_menampilkan_submission_dimana_dosen_menjadi_calon_pembimbing(): void
    {
        $this->actingAs($this->guide1)
            ->get(NuirSubmissionResource::getUrl('index', panel: 'dosen'))
            ->assertOk()
            ->assertSee($this->mahasiswa->name);
    }

    public function test_daftar_tidak_menampilkan_submission_dosen_lain(): void
    {
        $dosenLain = User::factory()->create()->assignRole('dosen');

        $this->actingAs($dosenLain)
            ->get(NuirSubmissionResource::getUrl('index', panel: 'dosen'))
            ->assertOk()
            ->assertDontSee($this->mahasiswa->name);
    }

    public function test_dosen_dapat_menyetujui_elemen_konten(): void
    {
        Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->call('approveContentField', 'title');

        $this->assertDatabaseHas('nuir_content_reviews', [
            'nuir_submission_id' => $this->submission->id,
            'user_id' => $this->guide1->id,
            'field' => 'title',
            'approved' => true,
        ]);
    }

    public function test_dosen_dapat_minta_revisi_elemen_konten_dan_tercatat_sebagai_pembimbing(): void
    {
        Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->call('requestContentFieldRevision', 'novelty', 'Perbaiki bagian novelty.');

        $this->assertDatabaseHas('nuir_content_reviews', [
            'nuir_submission_id' => $this->submission->id,
            'user_id' => $this->guide1->id,
            'field' => 'novelty',
            'approved' => false,
        ]);

        $event = NuirRevisionEvent::where('nuir_submission_id', $this->submission->id)
            ->where('subject', 'novelty')
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame(NuirRevisionEvent::ROLE_GUIDE1, $event->actor_role);
        $this->assertSame('Pembimbing 1', $event->actorRoleLabel());
    }

    public function test_dosen_dapat_membatalkan_persetujuan_elemen_konten(): void
    {
        $page = Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ]);

        $page->call('approveContentField', 'title');
        $page->call('cancelContentFieldApproval', 'title');

        $this->assertDatabaseMissing('nuir_content_reviews', [
            'nuir_submission_id' => $this->submission->id,
            'user_id' => $this->guide1->id,
            'field' => 'title',
            'approved' => true,
        ]);
    }

    public function test_kursi_diterima_otomatis_setelah_dosen_menyetujui_seluruh_elemen(): void
    {
        $page = Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ]);

        foreach (['title', 'novelty', 'urgency', 'impact'] as $field) {
            $page->call('approveContentField', $field);
        }

        $this->assertSame('accepted', $this->proposal->fresh()->guide1_status);
    }

    public function test_dosen_dapat_minta_revisi_referensi_dan_tercatat_sebagai_pembimbing_bukan_validator(): void
    {
        $reference = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        Livewire::actingAs($this->guide2)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->call('requestReferenceRevision', $reference->id, 'Link OJS mati.', ['link_ojs']);

        $reference->refresh();
        $this->assertFalse($reference->ref_approved);
        $this->assertSame('Link OJS mati.', $reference->ref_note);

        $event = NuirRevisionEvent::where('nuir_submission_id', $this->submission->id)
            ->where('ref_order', 1)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame(NuirRevisionEvent::ROLE_GUIDE2, $event->actor_role);
        $this->assertSame('Pembimbing 2', $event->actorRoleLabel());
    }

    public function test_panel_referensi_tidak_menampilkan_tombol_setujui_atau_batalkan_persetujuan(): void
    {
        NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->guide1)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'dosen'))
            ->assertOk()
            ->assertSee('Minta Revisi')
            ->assertDontSee('Setujui')
            ->assertDontSee('Batalkan Persetujuan');
    }

    public function test_dosen_dapat_menolak_usulan_dan_kuota_dilepaskan(): void
    {
        Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->callAction('rejectProposal', data: ['note' => 'Tidak sesuai bidang keahlian saya.'])
            ->assertHasNoActionErrors();

        $this->proposal->refresh();
        $this->assertSame('rejected', $this->proposal->guide1_status);
        $this->assertSame('Tidak sesuai bidang keahlian saya.', $this->proposal->guide1_note);

        $this->assertDatabaseHas('nuir_revision_events', [
            'nuir_submission_id' => $this->submission->id,
            'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_REJECTION,
            'actor_role' => NuirRevisionEvent::ROLE_GUIDE1,
        ]);
    }

    public function test_menolak_usulan_wajib_catatan(): void
    {
        Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->callAction('rejectProposal', data: ['note' => ''])
            ->assertHasActionErrors(['note']);

        $this->assertSame('pending', $this->proposal->fresh()->guide1_status);
    }

    public function test_istilah_status_review_konten_sesuai_ketentuan(): void
    {
        Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->call('approveContentField', 'title')
            ->call('requestContentFieldRevision', 'novelty', 'Perbaiki bagian novelty.');

        $this->actingAs($this->guide1)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'dosen'))
            ->assertOk()
            ->assertSee('Anda: Menyetujui')
            ->assertSee('Anda: Meminta Revisi')
            ->assertSee('Anda: Belum Mereview');
    }

    public function test_tombol_review_konten_tetap_tersedia_walau_submission_belum_content_ok(): void
    {
        $submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        NuirProposal::factory()->create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $this->guide1->id,
            'guide2_id' => $this->guide2->id,
        ]);

        $this->actingAs($this->guide1)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $submission], panel: 'dosen'))
            ->assertOk()
            ->assertSee('Setuju')
            ->assertSee('Minta Revisi');
    }

    public function test_tombol_tolak_usulan_tetap_tersedia_sebelum_semua_elemen_disetujui(): void
    {
        Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ])
            ->call('approveContentField', 'title')
            ->assertActionVisible('rejectProposal');
    }

    public function test_tombol_tolak_usulan_hilang_setelah_semua_elemen_disetujui(): void
    {
        $page = Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ]);

        foreach (['title', 'novelty', 'urgency', 'impact'] as $field) {
            $page->call('approveContentField', $field);
        }

        $page->assertActionHidden('rejectProposal');
    }

    public function test_tombol_batalkan_persetujuan_konten_hilang_setelah_semua_elemen_disetujui(): void
    {
        $page = Livewire::actingAs($this->guide1)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, [
                'record' => $this->submission->getRouteKey(),
            ]);

        foreach (['title', 'novelty', 'urgency', 'impact'] as $field) {
            $page->call('approveContentField', $field);
        }

        $this->actingAs($this->guide1)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'dosen'))
            ->assertOk()
            ->assertDontSee('Batalkan');
    }

    public function test_dosen_lain_tidak_dapat_membuka_submission_yang_bukan_usulannya(): void
    {
        $dosenLain = User::factory()->create()->assignRole('dosen');

        $this->actingAs($dosenLain)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'dosen'))
            ->assertNotFound();
    }
}
