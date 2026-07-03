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
use Database\Seeders\NuirSettingSeeder;
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

        $setting2026 = NuirSetting::where('year_generation', NuirSettingSeeder::YEAR_2026)->first();
        $this->assertNotNull($setting2026);
        $this->assertTrue($setting2026->active);
        $this->assertSame(1, $setting2026->stage);

        // Simulation mode: 8 students × 1 submission each (no DBS revision chain / v2)
        $this->assertGreaterThanOrEqual(8, NuirSubmission::count());
        $this->assertGreaterThanOrEqual(70, NuirReference::count());
        $this->assertGreaterThanOrEqual(4, NuirProposal::count());

        $statuses = NuirSubmission::pluck('status')->unique()->values()->all();
        $this->assertContains('title_slot', $statuses);
        $this->assertContains('submitted', $statuses);
        $this->assertContains('content_ok', $statuses);
        $this->assertContains('finalized', $statuses);
        // 'revision' status is no longer seeded in simulation mode (no DBS revision step)

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
            $this->assertContains($proposal->submission?->status, ['submitted', 'content_ok', 'finalized']);
            // guide1/guide2 can be null when manajer cancelled the seat
            if ($proposal->guide1_id) {
                $this->assertTrue($proposal->guide1?->hasRole('dosen'));
            }
            if ($proposal->guide2_id) {
                $this->assertTrue($proposal->guide2?->hasRole('dosen'));
            }
            // If both guides exist, they must be different
            if ($proposal->guide1_id && $proposal->guide2_id) {
                $this->assertNotSame($proposal->guide1_id, $proposal->guide2_id);
            }
        });

        $mahasiswa1 = User::where('username', NuirSimulationAccountSeeder::MAHASISWA_USERNAMES[1])->first();
        $this->assertNotNull($mahasiswa1);
        $this->assertTrue(
            NuirSubmission::where('user_id', $mahasiswa1->id)->where('status', 'title_slot')->exists()
        );

        $mahasiswa9 = User::where('username', NuirSimulationAccountSeeder::EMPTY_NUIR_STUDENT_USERNAME)->first();
        $this->assertNotNull($mahasiswa9);
        $this->assertFalse(NuirSubmission::where('user_id', $mahasiswa9->id)->exists());
        $this->assertTrue(
            GuideExaminer::where('user_id', $mahasiswa9->id)
                ->where('year_generation', $year)
                ->exists(),
            'Mahasiswa tanpa NUIR harus terdaftar di guide_examiners angkatan simulasi.',
        );

        $setting = NuirSetting::where('year_generation', $year)->first();
        $this->assertSame(10, $setting->max_references);
        $this->assertSame(12, $setting->min_words_novelty);
        $this->assertSame(300, $setting->max_words_novelty);
        $this->assertSame(300, $setting->max_words_urgency);
        $this->assertSame(300, $setting->max_words_impact);
        $this->assertSame(20, $setting->max_words_title);
        $this->assertSame(3, $setting->min_words_title);

        $pembimbing1 = User::where('username', 'pembimbing1')->first();
        $this->assertTrue(
            NuirProposal::where('guide1_id', $pembimbing1?->id)
                ->orWhere('guide2_id', $pembimbing1?->id)
                ->exists()
        );
        $this->assertTrue($pembimbing1?->hasRole('dosen'));
        $this->assertTrue(
            $pembimbing1?->hasRole('manajer nuir'),
            'pembimbing1 harus punya role manajer nuir agar bisa menjelajahi panel /nuir-manajer saat simulasi.',
        );
        $this->assertTrue(
            $pembimbing1?->hasRole('validator nuir'),
            'pembimbing1 harus punya role validator nuir agar bisa menjelajahi panel /nuir-validator saat simulasi.',
        );

        $pembimbing2 = User::where('username', 'pembimbing2')->first();
        $this->assertFalse($pembimbing2?->hasRole('manajer nuir'));
        $this->assertFalse($pembimbing2?->hasRole('validator nuir'));

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
            NuirRevisionEvent::where('event_type', NuirRevisionEvent::TYPE_NUI_REVISION)->count(),
            'Harus ada histori revisi NUI dari pembimbing (P1/P2).',
        );
        $this->assertGreaterThan(
            0,
            NuirRevisionEvent::where('event_type', NuirRevisionEvent::TYPE_PROPOSAL_REJECTION)->count(),
        );
        $this->assertGreaterThan(
            0,
            NuirRevisionEvent::where('event_type', NuirRevisionEvent::TYPE_PROPOSAL_CANCELLATION)->count(),
            'Harus ada histori pembatalan calon pembimbing oleh manajer.',
        );

        // mahasiswa5: P2 dibatalkan manajer, guide2_id harus null
        $mahasiswa5 = User::where('username', NuirSimulationAccountSeeder::MAHASISWA_USERNAMES[5])->first();
        $cancelledProposal = NuirProposal::whereHas('submission', fn ($q) => $q->where('user_id', $mahasiswa5?->id))
            ->whereNull('guide2_id')
            ->where('guide2_status', 'pending')
            ->first();
        $this->assertNotNull($cancelledProposal, 'mahasiswa5 harus punya proposal dengan P2 dibatalkan (guide2_id null).');

        $mahasiswa6 = User::where('username', NuirSimulationAccountSeeder::MAHASISWA_USERNAMES[6])->first();
        $partialSubmission = NuirSubmission::where('user_id', $mahasiswa6?->id)
            ->where('status', 'content_ok')
            ->first();
        $this->assertNotNull($partialSubmission);
        $this->assertSame(
            count(NuirContentReview::FIELDS),
            NuirContentReview::where('nuir_submission_id', $partialSubmission->id)
                ->where('user_id', $pembimbing1?->id)
                ->where('approved', true)
                ->count(),
        );

        // mahasiswa4: submitted with NUI revision requested by guides (no DBS involvement)
        $mahasiswa4 = User::where('username', NuirSimulationAccountSeeder::MAHASISWA_USERNAMES[4])->first();
        $nuiRevisionSubmission = NuirSubmission::where('user_id', $mahasiswa4?->id)
            ->where('status', 'submitted')
            ->first();
        $this->assertNotNull($nuiRevisionSubmission, 'mahasiswa4 harus punya submission submitted untuk revisi NUI pembimbing.');
        $this->assertTrue(
            NuirContentReview::where('nuir_submission_id', $nuiRevisionSubmission->id)
                ->where('field', 'novelty')
                ->where('approved', false)
                ->exists(),
            'mahasiswa4: novelty harus diminta revisi oleh P1.',
        );
        $this->assertTrue(
            NuirContentReview::where('nuir_submission_id', $nuiRevisionSubmission->id)
                ->where('field', 'impact')
                ->where('approved', false)
                ->exists(),
            'mahasiswa4: impact harus diminta revisi oleh P2.',
        );

        $mahasiswa2 = User::where('username', NuirSimulationAccountSeeder::MAHASISWA_USERNAMES[2])->first();
        $pembimbing2 = User::where('username', 'pembimbing2')->first();
        $dualRevisionSubmission = NuirSubmission::where('user_id', $mahasiswa2?->id)
            ->where('status', 'submitted')
            ->first();
        $this->assertNotNull($dualRevisionSubmission);
        $this->assertSame(
            2,
            NuirContentReview::where('nuir_submission_id', $dualRevisionSubmission->id)
                ->where('field', NuirContentReview::FIELD_IMPACT)
                ->where('approved', false)
                ->count(),
            'Impact mahasiswa2 harus diminta revisi oleh P1 dan P2 sebelum mahasiswa menyimpan perbaikan.',
        );
        $this->assertTrue(
            NuirContentReview::where('nuir_submission_id', $dualRevisionSubmission->id)
                ->where('field', NuirContentReview::FIELD_IMPACT)
                ->where('user_id', $pembimbing1?->id)
                ->where('approved', false)
                ->exists(),
        );
        $this->assertTrue(
            NuirContentReview::where('nuir_submission_id', $dualRevisionSubmission->id)
                ->where('field', NuirContentReview::FIELD_IMPACT)
                ->where('user_id', $pembimbing2?->id)
                ->where('approved', false)
                ->exists(),
        );
    }
}
