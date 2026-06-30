<?php

namespace Tests\Feature;

use App\Models\GuideAllocation;
use App\Models\GuideExaminer;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\NuirSimulationAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_membuat_simulasi_nuir_yang_konsisten(): void
    {
        $this->seed(DatabaseSeeder::class);

        $year = NuirSimulationAccountSeeder::SIMULATION_YEAR;
        $setting = NuirSetting::where('year_generation', $year)->first();
        $this->assertNotNull($setting);
        $this->assertTrue($setting->active);
        $this->assertSame(1, $setting->stage);

        $this->assertGreaterThanOrEqual(9, NuirSubmission::count());
        $this->assertGreaterThanOrEqual(70, NuirReference::count());
        $this->assertGreaterThanOrEqual(4, NuirProposal::count());

        $statuses = NuirSubmission::pluck('status')->unique()->values()->all();
        $this->assertContains('draft', $statuses);
        $this->assertContains('submitted', $statuses);
        $this->assertContains('revision', $statuses);
        $this->assertContains('content_ok', $statuses);
        $this->assertContains('finalized', $statuses);

        $revisionChild = NuirSubmission::whereNotNull('parent_submission_id')->first();
        $this->assertNotNull($revisionChild);
        $this->assertSame(2, $revisionChild->version);
        $this->assertSame('draft', $revisionChild->status);
        $this->assertSame('revision', $revisionChild->parentSubmission?->status);

        $finalized = NuirSubmission::where('status', 'finalized')->first();
        $this->assertNotNull($finalized);
        $finalProposal = NuirProposal::where('nuir_submission_id', $finalized->id)->where('final', true)->first();
        $this->assertNotNull($finalProposal);

        $guideExaminer = GuideExaminer::where('user_id', $finalized->user_id)->first();
        $this->assertNotNull($guideExaminer);
        $this->assertSame($finalProposal->guide1_id, $guideExaminer->guide1_id);
        $this->assertSame($finalProposal->guide2_id, $guideExaminer->guide2_id);

        $retriedSubmission = NuirProposal::select('nuir_submission_id')
            ->groupBy('nuir_submission_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();
        $this->assertNotNull($retriedSubmission);

        $partialProposal = NuirProposal::where('guide1_status', 'accepted')
            ->where('guide2_status', 'pending')
            ->first();
        $this->assertNotNull($partialProposal);

        $nuirStudents = NuirSubmission::pluck('user_id')->unique();
        $nuirStudents->each(function (int $userId) use ($year) {
            $student = User::find($userId);
            $this->assertTrue($student?->hasRole('mahasiswa'));

            $ge = GuideExaminer::where('user_id', $userId)->first();
            $this->assertNotNull($ge);
            $this->assertSame($year, $ge->year_generation);
        });

        NuirReference::all()->each(function (NuirReference $reference) {
            $this->assertNotNull($reference->submission);
            $this->assertGreaterThanOrEqual(1, $reference->ref_order);
        });

        NuirProposal::all()->each(function (NuirProposal $proposal) {
            $this->assertContains($proposal->submission?->status, ['submitted', 'revision', 'content_ok', 'finalized']);
            $this->assertTrue($proposal->guide1?->hasRole('dosen'));
            $this->assertTrue($proposal->guide2?->hasRole('dosen'));
            $this->assertNotSame($proposal->guide1_id, $proposal->guide2_id);
        });

        $mahasiswa1 = User::where('username', 'mahasiswa1')->first();
        $this->assertNotNull($mahasiswa1);
        $this->assertTrue(
            NuirSubmission::where('user_id', $mahasiswa1->id)->where('status', 'draft')->exists()
        );

        $pembimbing1 = User::where('username', 'pembimbing1')->first();
        $this->assertTrue(
            NuirProposal::where('guide1_id', $pembimbing1?->id)
                ->orWhere('guide2_id', $pembimbing1?->id)
                ->exists()
        );

        $yearInt = (int) $year;
        $this->assertGreaterThanOrEqual(
            5,
            GuideAllocation::where('year', $yearInt)->where('active', true)->count(),
            'Simulasi harus menyiapkan kuota pembimbing angkatan 2099.',
        );

        $this->assertTrue(
            GuideAllocation::where('user_id', User::where('username', 'penguji3')->value('id'))
                ->where('year', $yearInt)
                ->where('guide1_quota', 0)
                ->where('guide2_quota', '>', 0)
                ->exists(),
            'penguji3 harus hanya punya kuota P2 untuk uji filter posisi.',
        );

        $this->assertGreaterThan(
            0,
            NuirRevisionEvent::where('event_type', NuirRevisionEvent::TYPE_REFERENCE_REVISION)->count(),
        );
        $this->assertGreaterThan(
            0,
            NuirRevisionEvent::where('event_type', NuirRevisionEvent::TYPE_DBS_REVISION)->count(),
        );
        $this->assertGreaterThan(
            0,
            NuirRevisionEvent::where('event_type', NuirRevisionEvent::TYPE_PROPOSAL_REJECTION)->count(),
        );

        $mahasiswa6 = User::where('username', 'mahasiswa6')->first();
        $partialSubmission = NuirSubmission::where('user_id', $mahasiswa6?->id)
            ->where('status', 'content_ok')
            ->first();
        $this->assertNotNull($partialSubmission);
        $this->assertSame(
            3,
            NuirContentReview::where('nuir_submission_id', $partialSubmission->id)
                ->where('user_id', $pembimbing1?->id)
                ->where('approved', true)
                ->count(),
        );
    }
}
