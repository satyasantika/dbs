<?php

namespace Tests\Unit;

use App\Models\NuirRevisionEvent;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Support\NuirValidatorReferenceStatus;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirValidatorReferenceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_counts_membedakan_pending_dan_menunggu_validasi_ulang(): void
    {
        $this->seed(PermissionSeeder::class);

        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
        ]);

        $submission->references()->create([
            'ref_order' => 1,
            'ref_approved' => true,
        ]);
        $submission->references()->create([
            'ref_order' => 2,
            'ref_approved' => false,
        ]);
        $submission->references()->create([
            'ref_order' => 3,
            'ref_approved' => null,
        ]);
        $submission->references()->create([
            'ref_order' => 4,
            'ref_approved' => null,
        ]);

        NuirRevisionEvent::create([
            'nuir_submission_id' => $submission->id,
            'submission_version' => 1,
            'actor_id' => User::factory()->create()->id,
            'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
            'subject' => '4',
            'ref_order' => 4,
            'note' => 'Perbaiki link.',
            'recorded_at' => now(),
        ]);

        $submission->load('references');

        $this->assertSame([
            'approved' => 1,
            'needs_revision' => 1,
            'pending' => 1,
            'awaiting_revalidation' => 1,
        ], NuirValidatorReferenceStatus::referenceCounts($submission));

        $this->assertSame(
            '1 disetujui · 2 pending · 1 revisi',
            NuirValidatorReferenceStatus::referenceBreakdownSummary($submission),
        );
    }

    public function test_submission_activity_summary_menampilkan_tanggal_penugasan_sebelum_respon(): void
    {
        $this->seed(PermissionSeeder::class);

        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'updated_at' => now()->subDay(),
        ]);

        $submission->references()->create(['ref_order' => 1, 'ref_approved' => null]);
        $submission->references()->create(['ref_order' => 2, 'ref_approved' => null]);

        $assignedAt = now()->subWeek();
        $submission->assignment()->create([
            'validator_id' => User::factory()->create()->assignRole('validator nuir')->id,
            'assigned_by' => User::factory()->create()->assignRole('manajer nuir')->id,
            'assigned_at' => $assignedAt,
        ]);

        $submission->load(['references', 'assignment']);

        $this->assertFalse(NuirValidatorReferenceStatus::validatorHasRespondedToAnyReference($submission));
        $this->assertStringStartsWith(
            'Ditugaskan ',
            NuirValidatorReferenceStatus::submissionActivitySummary($submission),
        );
        $this->assertStringNotContainsString(
            'Diperbarui',
            NuirValidatorReferenceStatus::submissionActivitySummary($submission),
        );
    }

    public function test_submission_activity_summary_menampilkan_di_perbarui_setelah_respon(): void
    {
        $this->seed(PermissionSeeder::class);

        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'updated_at' => now()->subHours(3),
        ]);

        $submission->references()->create(['ref_order' => 1, 'ref_approved' => true]);
        $submission->references()->create(['ref_order' => 2, 'ref_approved' => null]);

        $submission->assignment()->create([
            'validator_id' => User::factory()->create()->assignRole('validator nuir')->id,
            'assigned_by' => User::factory()->create()->assignRole('manajer nuir')->id,
            'assigned_at' => now()->subWeek(),
        ]);

        $submission->load(['references', 'assignment']);

        $this->assertTrue(NuirValidatorReferenceStatus::validatorHasRespondedToAnyReference($submission));
        $this->assertStringStartsWith(
            'Diperbarui ',
            NuirValidatorReferenceStatus::submissionActivitySummary($submission),
        );
        $this->assertStringNotContainsString(
            'Ditugaskan',
            NuirValidatorReferenceStatus::submissionActivitySummary($submission),
        );
    }

    public function test_reference_breakdown_badges_memiliki_warna_terpisah(): void
    {
        $this->seed(PermissionSeeder::class);

        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
        ]);

        $submission->references()->create(['ref_order' => 1, 'ref_approved' => true]);
        $submission->references()->create(['ref_order' => 2, 'ref_approved' => false]);
        $submission->references()->create(['ref_order' => 3, 'ref_approved' => null]);

        $submission->load('references');

        $this->assertSame([
            ['label' => '1 disetujui', 'color' => 'success'],
            ['label' => '1 pending', 'color' => 'warning'],
            ['label' => '1 revisi', 'color' => 'danger'],
        ], NuirValidatorReferenceStatus::referenceBreakdownBadges($submission));
    }

    public function test_reference_activity_summary_menampilkan_penugasan_sebelum_respon_per_referensi(): void
    {
        $this->seed(PermissionSeeder::class);

        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
            'updated_at' => now()->subDay(),
        ]);

        $reference = $submission->references()->create([
            'ref_order' => 1,
            'ref_approved' => null,
            'updated_at' => now()->subDay(),
        ]);

        $submission->assignment()->create([
            'validator_id' => User::factory()->create()->assignRole('validator nuir')->id,
            'assigned_by' => User::factory()->create()->assignRole('manajer nuir')->id,
            'assigned_at' => now()->subWeek(),
        ]);

        $reference->load('submission.assignment');

        $this->assertFalse(NuirValidatorReferenceStatus::referenceHasValidatorResponse($reference));
        $this->assertStringStartsWith(
            'Ditugaskan ',
            NuirValidatorReferenceStatus::referenceActivitySummary($reference),
        );
    }

    public function test_reference_activity_summary_menampilkan_di_perbarui_setelah_respon_per_referensi(): void
    {
        $this->seed(PermissionSeeder::class);

        $submission = NuirSubmission::factory()->submitted()->create([
            'user_id' => User::factory()->create()->assignRole('mahasiswa')->id,
        ]);

        $reference = $submission->references()->create([
            'ref_order' => 1,
            'ref_approved' => null,
            'updated_at' => now()->subHours(2),
        ]);

        NuirRevisionEvent::create([
            'nuir_submission_id' => $submission->id,
            'submission_version' => 1,
            'actor_id' => User::factory()->create()->id,
            'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
            'subject' => '1',
            'ref_order' => 1,
            'note' => 'Perbaiki.',
            'recorded_at' => now()->subDay(),
        ]);

        $reference->load('submission.assignment');

        $this->assertTrue(NuirValidatorReferenceStatus::referenceHasValidatorResponse($reference));
        $this->assertStringStartsWith(
            'Diperbarui ',
            NuirValidatorReferenceStatus::referenceActivitySummary($reference),
        );
    }
}
