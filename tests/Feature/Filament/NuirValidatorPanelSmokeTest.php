<?php

namespace Tests\Feature\Filament;

use App\Filament\NuirManajer\Resources\NuirSubmissionResource as ManajerSubmissionResource;
use App\Filament\NuirValidator\Pages\Dashboard as ValidatorDashboard;
use App\Filament\NuirValidator\Resources\NuirReferenceResource as ValidatorReferenceResource;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource as ValidatorSubmissionResource;
use App\Support\NuirValidatorListReturn;
use App\Models\GuideExaminer;
use App\Models\NuirAssignment;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
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
            ->assertSee('Panel validator NUIR')
            ->assertSee('Submission Ditugaskan')
            ->assertSee('Validasi Selesai');
    }

    public function test_validator_dapat_akses_daftar_dan_detail_submission(): void
    {
        $pendingReference = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
            'ref_approved' => null,
        ]);

        $approvedReference = NuirReference::factory()->verifiable()->approved()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 2,
        ]);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::listUrl(ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED))
            ->assertOk()
            ->assertSee($this->submission->user->name)
            ->assertSee('Referensi');

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Referensi #1')
            ->assertSee('Referensi #2')
            ->assertSee('Kutipan (terakhir)')
            ->assertSee('Setujui')
            ->assertSee('Minta Revisi')
            ->assertSee('Bagian yang perlu diperbaiki')
            ->assertSee('Link OJS')
            ->assertSee('Buka')
            ->assertSee('Kembali ke Daftar Submission');

        $html = $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'wire:click="approveReference'));
        $this->assertSame(1, substr_count($html, 'Minta Revisi'));

        $focusedHtml = $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::viewUrl($this->submission, $pendingReference->id, panel: 'nuir-validator'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('wire:key="validator-reference-'.$pendingReference->id.'"', $focusedHtml);
        $this->assertMatchesRegularExpression(
            '/wire:key="validator-reference-'.$pendingReference->id.'"[^>]*x-data="\{ open: true \}"/s',
            $focusedHtml,
        );
        $this->assertMatchesRegularExpression(
            '/wire:key="validator-reference-'.$approvedReference->id.'"[^>]*x-data="\{ open: false \}"/s',
            $focusedHtml,
        );
    }

    public function test_tombol_kembali_mengikuti_daftar_asal_trigger(): void
    {
        $pendingReference = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
            'ref_approved' => null,
        ]);

        $assignedListUrl = ValidatorSubmissionResource::listUrl(
            ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED,
        );
        $pendingReferencesListUrl = ValidatorReferenceResource::listUrl(
            ValidatorReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES,
        );
        $awaitingRevalidationListUrl = ValidatorReferenceResource::listUrl(
            ValidatorReferenceResource::DASHBOARD_VIEW_AWAITING_REVALIDATION,
        );
        $validationCompleteListUrl = ValidatorSubmissionResource::listUrl(
            ValidatorSubmissionResource::DASHBOARD_VIEW_VALIDATION_COMPLETE,
        );

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::viewUrl(
                $this->submission,
                returnTo: NuirValidatorListReturn::submissionKey(
                    ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED,
                ),
                panel: 'nuir-validator',
            ))
            ->assertOk()
            ->assertSee('Kembali ke Submission Ditugaskan')
            ->assertSee($assignedListUrl, false);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::viewUrl(
                $this->submission,
                $pendingReference->id,
                NuirValidatorListReturn::referenceKey(
                    ValidatorReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES,
                ),
                panel: 'nuir-validator',
            ))
            ->assertOk()
            ->assertSee('Kembali ke Referensi Pending')
            ->assertSee($pendingReferencesListUrl, false);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::viewUrl(
                $this->submission,
                returnTo: NuirValidatorListReturn::referenceKey(
                    ValidatorReferenceResource::DASHBOARD_VIEW_AWAITING_REVALIDATION,
                ),
                panel: 'nuir-validator',
            ))
            ->assertOk()
            ->assertSee('Kembali ke Permintaan Revisi')
            ->assertSee($awaitingRevalidationListUrl, false);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::viewUrl(
                $this->submission,
                returnTo: NuirValidatorListReturn::submissionKey(
                    ValidatorSubmissionResource::DASHBOARD_VIEW_VALIDATION_COMPLETE,
                ),
                panel: 'nuir-validator',
            ))
            ->assertOk()
            ->assertSee('Kembali ke Validasi Selesai')
            ->assertSee($validationCompleteListUrl, false);

        $assignedListHtml = $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::listUrl(ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'return='.urlencode(NuirValidatorListReturn::submissionKey(
                ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED,
            )),
            $assignedListHtml,
        );

        $pendingReferencesListHtml = $this->actingAs($this->validator)
            ->get(ValidatorReferenceResource::listUrl(
                ValidatorReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES,
            ))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'return='.urlencode(NuirValidatorListReturn::referenceKey(
                ValidatorReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES,
            )),
            $pendingReferencesListHtml,
        );
    }

    public function test_ringkasan_detail_menampilkan_nim_dan_dokumen_nuir_hanya_jika_ada(): void
    {
        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('NIM')
            ->assertSee($this->mahasiswa->username)
            ->assertDontSee('Dokumen NUIR (Google Drive)', false);

        $this->submission->update(['nuir_document_link' => 'https://drive.google.com/file/d/xyz/view']);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Dokumen NUIR (Google Drive)', false)
            ->assertSee('https://drive.google.com/file/d/xyz/view', false);
    }

    public function test_status_detail_referensi_belum_divalidasi_saat_belum_satupun_disetujui(): void
    {
        NuirReference::factory()->verifiable()->count(2)->sequence(
            ['ref_order' => 1],
            ['ref_order' => 2],
        )->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_approved' => null,
        ]);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Referensi Belum Divalidasi');
    }

    public function test_status_detail_referensi_sebagian_divalidasi_saat_masih_ada_yang_belum(): void
    {
        NuirReference::factory()->verifiable()->approved()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 2,
            'ref_approved' => null,
        ]);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Referensi Sebagian Divalidasi');
    }

    public function test_status_detail_referensi_tervalidasi_saat_semua_disetujui(): void
    {
        NuirReference::factory()->verifiable()->approved()->count(2)->sequence(
            ['ref_order' => 1],
            ['ref_order' => 2],
        )->create([
            'nuir_submission_id' => $this->submission->id,
        ]);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Referensi Tervalidasi');
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
            ->get(ValidatorSubmissionResource::listUrl(ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED))
            ->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_akses_panel_validator(): void
    {
        $this->actingAs($this->mahasiswa)
            ->get(ValidatorDashboard::getUrl(panel: 'nuir-validator'))
            ->assertForbidden();
    }

    public function test_daftar_submission_terfilter_sesuai_kartu_dashboard(): void
    {
        $pendingUser = User::factory()->create(['name' => 'Mhs Ref Pending'])->assignRole('mahasiswa');
        $pendingSubmission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $pendingUser->id,
            'year_generation' => '2022',
        ]);
        NuirAssignment::create([
            'nuir_submission_id' => $pendingSubmission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $pendingSubmission->id,
            'ref_order' => 1,
            'ref_approved' => null,
        ]);

        $completeUser = User::factory()->create(['name' => 'Mhs Validasi Selesai'])->assignRole('mahasiswa');
        $completeSubmission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $completeUser->id,
            'year_generation' => '2022',
        ]);
        NuirAssignment::create([
            'nuir_submission_id' => $completeSubmission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $completeSubmission->id,
            'ref_order' => 1,
        ]);
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $completeSubmission->id,
            'ref_order' => 2,
        ]);

        $revalidationUser = User::factory()->create(['name' => 'Mhs Validasi Ulang'])->assignRole('mahasiswa');
        $revalidationSubmission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $revalidationUser->id,
            'year_generation' => '2022',
        ]);
        NuirAssignment::create([
            'nuir_submission_id' => $revalidationSubmission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);
        NuirReference::factory()->create([
            'nuir_submission_id' => $revalidationSubmission->id,
            'ref_order' => 1,
            'ref_approved' => null,
        ]);
        NuirRevisionEvent::create([
            'nuir_submission_id' => $revalidationSubmission->id,
            'submission_version' => 1,
            'actor_id' => $this->validator->id,
            'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
            'subject' => '1',
            'ref_order' => 1,
            'note' => 'Perbaiki DOI.',
            'recorded_at' => now(),
        ]);

        $unassignedUser = User::factory()->create(['name' => 'Mhs Tanpa Delegasi'])->assignRole('mahasiswa');
        NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $unassignedUser->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::listUrl(ValidatorSubmissionResource::DASHBOARD_VIEW_ASSIGNED))
            ->assertOk()
            ->assertSee('Submission Ditugaskan')
            ->assertSee('Referensi')
            ->assertSee('Dashboard')
            ->assertSee(route('home'), false)
            ->assertSee('Mhs Ref Pending')
            ->assertSee('Mhs Validasi Selesai')
            ->assertSee('Mhs Validasi Ulang')
            ->assertSee($this->submission->user->name)
            ->assertDontSee('Mhs Tanpa Delegasi');

        $this->actingAs($this->validator)
            ->get(ValidatorReferenceResource::listUrl(ValidatorReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES))
            ->assertOk()
            ->assertSee('Referensi Pending')
            ->assertSee('Kembali ke Dashboard')
            ->assertSee('Mhs Ref Pending')
            ->assertDontSee('Mhs Validasi Ulang')
            ->assertDontSee('Mhs Validasi Selesai');

        $this->actingAs($this->validator)
            ->get(ValidatorSubmissionResource::listUrl(ValidatorSubmissionResource::DASHBOARD_VIEW_VALIDATION_COMPLETE))
            ->assertOk()
            ->assertSee('Validasi Selesai')
            ->assertSee('Dashboard')
            ->assertSee('Mhs Validasi Selesai')
            ->assertSee('Disetujui')
            ->assertDontSee('Mhs Ref Pending')
            ->assertDontSee('Referensi');

        $this->actingAs($this->validator)
            ->get(ValidatorReferenceResource::listUrl(ValidatorReferenceResource::DASHBOARD_VIEW_AWAITING_REVALIDATION))
            ->assertOk()
            ->assertSee('Permintaan Revisi')
            ->assertSee('Kembali ke Dashboard')
            ->assertSee('Mhs Validasi Ulang')
            ->assertSee('Menunggu validasi ulang')
            ->assertDontSee('Mhs Ref Pending')
            ->assertDontSee('Mhs Validasi Selesai');
    }

    public function test_panel_validator_menggunakan_sidebar_dan_nama_portal_validator_nuir(): void
    {
        $panel = Filament::getPanel('nuir-validator');

        $this->assertFalse($panel->hasTopNavigation());
        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertStringContainsString('Portal Validator NUIR', (string) $panel->getBrandName());

        $this->actingAs($this->validator)
            ->get(ValidatorDashboard::getUrl(panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Portal Validator NUIR');
    }

    public function test_validator_dengan_satu_role_tidak_melihat_select_role(): void
    {
        $this->actingAs($this->validator)
            ->get(ValidatorDashboard::getUrl(panel: 'nuir-validator'))
            ->assertOk()
            ->assertDontSee('id="role-switcher"', false);
    }

    public function test_validator_dengan_role_ganda_melihat_select_role(): void
    {
        $this->validator->assignRole('dosen');

        $this->actingAs($this->validator)
            ->get(ValidatorDashboard::getUrl(panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('id="role-switcher"', false)
            ->assertSeeInOrder(['Portal Dosen', 'Portal Validator NUIR']);
    }
}
