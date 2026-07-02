<?php

namespace Tests\Feature;

use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Models\GuideExaminer;
use App\Models\NuirAssignment;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirAssignmentService;
use App\Support\NuirReferenceExistence;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class NuirValidatorRoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $manajer;

    protected User $validator;

    protected User $validatorLain;

    protected User $mahasiswa;

    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->manajer = User::factory()->create()->assignRole('manajer nuir');
        $this->validator = User::factory()->create()->assignRole('validator nuir');
        $this->validatorLain = User::factory()->create()->assignRole('validator nuir');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');

        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('nuir-validator'));

        NuirAssignment::create([
            'nuir_submission_id' => $this->submission->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_validator_hanya_dapat_review_submission_yang_didelegasikan(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->validatorLain);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validatorLain, true);
    }

    public function test_validator_dapat_memvalidasi_eksistensi_dan_menyetujui_referensi_lengkap(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->assertTrue(NuirReferenceExistence::isVerifiable($ref));

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);

        $this->assertTrue($ref->fresh()->ref_approved);
        $this->assertNull($ref->fresh()->ref_note);
    }

    public function test_validator_tidak_dapat_menyetujui_referensi_yang_belum_lengkap(): void
    {
        $ref = NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
            'link_ojs' => null,
            'indexer_name' => 'Scopus',
            'link_index' => null,
        ]);

        $this->assertFalse(NuirReferenceExistence::isVerifiable($ref));

        $this->expectException(ValidationException::class);
        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);
    }

    public function test_validator_dapat_membatalkan_persetujuan_referensi(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);
        $this->assertTrue($ref->fresh()->ref_approved);

        app(NuirAssignmentService::class)->cancelReferenceApprovalAsValidator($ref->fresh(), $this->validator);

        $this->assertNull($ref->fresh()->ref_approved);
    }

    public function test_validator_lain_tidak_dapat_membatalkan_persetujuan_referensi(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);

        $this->actingAs($this->validatorLain);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(NuirAssignmentService::class)->cancelReferenceApprovalAsValidator($ref->fresh(), $this->validatorLain);
    }

    public function test_referensi_disetujui_menampilkan_tombol_batalkan_persetujuan(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->validator)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertDontSee('Batalkan Persetujuan');

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);

        $this->actingAs($this->validator)
            ->get(NuirSubmissionResource::getUrl('view', ['record' => $this->submission], panel: 'nuir-validator'))
            ->assertOk()
            ->assertSee('Batalkan Persetujuan');
    }

    public function test_cancel_reference_approval_livewire_action_mengembalikan_ke_pending(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);

        Livewire::actingAs($this->validator)
            ->test(NuirSubmissionResource\Pages\ViewNuirSubmission::class, ['record' => $this->submission->getRouteKey()])
            ->call('cancelReferenceApproval', $ref->id)
            ->assertHasNoErrors();

        $this->assertNull($ref->fresh()->ref_approved);
    }

    public function test_validator_dapat_minta_revisi_per_referensi_dengan_catatan(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator(
            $ref,
            $this->validator,
            false,
            'DOI tidak dapat diverifikasi',
            ['link_ojs', 'link_index'],
        );

        $this->assertFalse($ref->fresh()->ref_approved);
        $this->assertEquals('DOI tidak dapat diverifikasi', $ref->fresh()->ref_note);
        $this->assertEquals(['link_ojs', 'link_index'], $ref->fresh()->ref_revision_fields);
    }

    public function test_minta_revisi_referensi_wajib_pilih_bagian(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 3,
        ]);

        $this->expectException(ValidationException::class);
        app(NuirAssignmentService::class)->reviewReferenceAsValidator(
            $ref,
            $this->validator,
            false,
            'Perlu diperbaiki',
            [],
        );
    }

    public function test_minta_revisi_referensi_wajib_catatan(): void
    {
        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 2,
        ]);

        $this->expectException(ValidationException::class);
        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, false, '');
    }

    public function test_review_referensi_dilakukan_per_referensi_secara_terpisah(): void
    {
        $ref1 = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);
        $ref2 = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 2,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref1, $this->validator, true);

        $this->assertTrue($ref1->fresh()->ref_approved);
        $this->assertNull($ref2->fresh()->ref_approved);
    }

    public function test_validator_tidak_dapat_review_submission_draft(): void
    {
        $draft = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
        ]);

        NuirAssignment::create([
            'nuir_submission_id' => $draft->id,
            'validator_id' => $this->validator->id,
            'assigned_by' => $this->manajer->id,
            'assigned_at' => now(),
        ]);

        $ref = NuirReference::factory()->verifiable()->create([
            'nuir_submission_id' => $draft->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->validator);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref, $this->validator, true);
    }

    public function test_perbaikan_referensi_mengosongkan_keputusan_validator(): void
    {
        $ref = NuirReference::factory()->verifiable()->rejected('Link mati')->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $ref->update([
            'link_ojs' => 'https://ojs.example.com/article/baru',
            'link_index' => 'https://www.scopus.com/record/baru',
            'ref_approved' => null,
            'ref_note' => null,
            'ref_revision_fields' => null,
        ]);

        app(NuirAssignmentService::class)->reviewReferenceAsValidator($ref->fresh(), $this->validator, true);

        $this->assertTrue($ref->fresh()->ref_approved);
        $this->assertNull($ref->fresh()->ref_note);
    }
}
