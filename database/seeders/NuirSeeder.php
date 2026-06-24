<?php

namespace Database\Seeders;

use App\Models\GuideExaminer;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class NuirSeeder extends Seeder
{
    private const YEAR = '2021';

    private const MIN_REFS = 10;

    private User $dbs;

    /** @var Collection<int, User> */
    private Collection $lecturers;

    public function run(): void
    {
        $dbs = User::role('dbs')->first();
        $lecturers = User::role('dosen')->get();

        if (! $dbs || $lecturers->count() < 4) {
            $this->command?->warn('NuirSeeder: butuh akun DBS dan minimal 4 dosen, dilewati.');

            return;
        }

        $students = User::role('mahasiswa')
            ->orderBy('id')
            ->skip(8)
            ->take(8)
            ->get();

        if ($students->count() < 8) {
            $this->command?->warn('NuirSeeder: butuh minimal 8 mahasiswa (setelah 8 pertama ExamRegistrationSeeder), dilewati.');

            return;
        }

        $this->dbs = $dbs;
        $this->lecturers = $lecturers;

        $this->seedSettings();
        $students->each(fn (User $student) => $this->ensureGuideExaminer($student));

        [
            $draftStudent,
            $submittedStudent,
            $reviewReadyStudent,
            $revisionStudent,
            $proposalPendingStudent,
            $proposalPartialStudent,
            $proposalRetriedStudent,
            $finalizedStudent,
        ] = $students->all();

        $this->seedDraftSubmission($draftStudent);
        $this->seedSubmittedSubmission($submittedStudent);
        $this->seedSubmittedReadyForReview($reviewReadyStudent);
        $this->seedRevisionChain($revisionStudent);
        $this->seedContentOkWithPendingProposal($proposalPendingStudent);
        $this->seedContentOkWithPartialAcceptance($proposalPartialStudent);
        $this->seedContentOkWithRetriedProposal($proposalRetriedStudent);
        $this->seedFinalizedFlow($finalizedStudent);

        $this->command?->info(sprintf(
            'NuirSeeder: %d setting, %d submission, %d referensi, %d proposal untuk angkatan %s.',
            NuirSetting::count(),
            NuirSubmission::count(),
            NuirReference::count(),
            NuirProposal::count(),
            self::YEAR,
        ));
    }

    private function seedSettings(): void
    {
        NuirSetting::updateOrCreate(
            ['year_generation' => self::YEAR],
            [
                'stage' => 1,
                'active' => true,
                'deadline' => now()->addMonths(2)->toDateString(),
                'min_references_approved' => self::MIN_REFS,
                'max_chars_novelty' => 5000,
                'max_chars_urgency' => 5000,
                'max_chars_impact' => 5000,
            ],
        );

        NuirSetting::updateOrCreate(
            ['year_generation' => '2020'],
            [
                'stage' => 3,
                'active' => false,
                'deadline' => null,
                'min_references_approved' => self::MIN_REFS,
            ],
        );

        NuirSetting::updateOrCreate(
            ['year_generation' => '2023'],
            [
                'stage' => 2,
                'active' => false,
                'deadline' => now()->addYear()->toDateString(),
                'min_references_approved' => 1,
            ],
        );
    }

    private function ensureGuideExaminer(User $student): GuideExaminer
    {
        return GuideExaminer::firstOrCreate(
            ['user_id' => $student->id],
            [
                'year_generation' => self::YEAR,
                'examiner1_id' => null,
                'examiner2_id' => null,
                'examiner3_id' => null,
                'guide1_id' => null,
                'guide2_id' => null,
                'chief_id' => null,
            ],
        );
    }

    private function seedDraftSubmission(User $student): void
    {
        $submission = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => self::YEAR,
            'title' => 'Draft NUIR — '.$student->name,
            'novelty' => 'Novelty draft simulasi untuk pengujian alur mahasiswa.',
            'urgency' => 'Urgency draft simulasi untuk pengujian alur mahasiswa.',
            'impact' => 'Impact draft simulasi untuk pengujian alur mahasiswa.',
            'status' => 'draft',
        ]);

        $this->seedReferences($submission, total: 8, approved: 0, rejected: 0);
    }

    private function seedSubmittedSubmission(User $student): void
    {
        $submission = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => self::YEAR,
            'title' => 'Submitted NUIR — '.$student->name,
            'novelty' => 'Novelty submitted simulasi menunggu review DBS.',
            'urgency' => 'Urgency submitted simulasi menunggu review DBS.',
            'impact' => 'Impact submitted simulasi menunggu review DBS.',
            'status' => 'submitted',
        ]);

        $this->seedReferences($submission, total: self::MIN_REFS, approved: 5, rejected: 2);
    }

    private function seedSubmittedReadyForReview(User $student): void
    {
        $submission = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => self::YEAR,
            'title' => 'Siap Review Konten — '.$student->name,
            'novelty' => 'Novelty siap disetujui DBS setelah referensi lengkap.',
            'urgency' => 'Urgency siap disetujui DBS setelah referensi lengkap.',
            'impact' => 'Impact siap disetujui DBS setelah referensi lengkap.',
            'status' => 'submitted',
        ]);

        $this->seedReferences($submission, total: self::MIN_REFS, approved: self::MIN_REFS, rejected: 0);
    }

    private function seedRevisionChain(User $student): void
    {
        $versionOne = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => self::YEAR,
            'title' => 'Revisi v1 — '.$student->name,
            'novelty' => 'Novelty versi 1 yang diminta revisi DBS.',
            'urgency' => 'Urgency versi 1 yang diminta revisi DBS.',
            'impact' => 'Impact versi 1 yang diminta revisi DBS.',
            'status' => 'revision',
            'dbs_note' => 'Perbaiki referensi SINTA dan perjelas urgensi penelitian.',
            'dbs_reviewer_id' => $this->dbs->id,
            'dbs_reviewed_at' => now()->subDays(3),
        ]);

        $this->seedReferences($versionOne, total: self::MIN_REFS, approved: 4, rejected: 3);

        $versionTwo = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => self::YEAR,
            'parent_submission_id' => $versionOne->id,
            'version' => 2,
            'title' => 'Revisi v2 — '.$student->name,
            'novelty' => 'Novelty versi 2 hasil perbaikan mahasiswa.',
            'urgency' => 'Urgency versi 2 hasil perbaikan mahasiswa.',
            'impact' => 'Impact versi 2 hasil perbaikan mahasiswa.',
            'status' => 'draft',
        ]);

        $this->seedReferences($versionTwo, total: self::MIN_REFS, approved: 0, rejected: 0);
    }

    private function seedContentOkWithPendingProposal(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Proposal Pending');
        [$guide1, $guide2] = $this->pickGuidePair(0);

        NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
        ]);
    }

    private function seedContentOkWithPartialAcceptance(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Proposal Sebagian Diterima');
        [$guide1, $guide2] = $this->pickGuidePair(1);

        NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
            'guide1_status' => 'accepted',
            'guide1_responded_at' => now()->subDay(),
        ]);
    }

    private function seedContentOkWithRetriedProposal(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Proposal Ulang');
        [$rejectedGuide1, $rejectedGuide2] = $this->pickGuidePair(2);
        [$pendingGuide1, $pendingGuide2] = $this->pickGuidePair(3);

        NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $rejectedGuide1->id,
            'guide2_id' => $rejectedGuide2->id,
            'guide1_status' => 'rejected',
            'guide2_status' => 'rejected',
            'guide1_note' => 'Kuota bimbingan penuh.',
            'guide2_note' => 'Tidak sesuai minat bimbingan.',
            'guide1_responded_at' => now()->subDays(5),
            'guide2_responded_at' => now()->subDays(4),
        ]);

        NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $pendingGuide1->id,
            'guide2_id' => $pendingGuide2->id,
        ]);
    }

    private function seedFinalizedFlow(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Finalized');
        [$guide1, $guide2] = $this->pickGuidePair(4);

        $proposal = NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
            'guide1_status' => 'accepted',
            'guide2_status' => 'accepted',
            'guide1_responded_at' => now()->subDays(2),
            'guide2_responded_at' => now()->subDay(),
        ]);

        app(NuirService::class)->finalizeProposal($proposal->fresh());

        GuideExaminer::where('user_id', $student->id)->update([
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
        ]);
    }

    private function createContentOkSubmission(User $student, string $label): NuirSubmission
    {
        $submission = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => self::YEAR,
            'title' => "{$label} — {$student->name}",
            'novelty' => "Novelty {$label} simulasi alur proposal pembimbing.",
            'urgency' => "Urgency {$label} simulasi alur proposal pembimbing.",
            'impact' => "Impact {$label} simulasi alur proposal pembimbing.",
            'status' => 'content_ok',
            'dbs_reviewer_id' => $this->dbs->id,
            'dbs_reviewed_at' => now()->subWeek(),
        ]);

        $this->seedReferences($submission, total: self::MIN_REFS, approved: self::MIN_REFS, rejected: 0);

        return $submission;
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function pickGuidePair(int $offset): array
    {
        $count = $this->lecturers->count();
        $first = $this->lecturers->get($offset % $count);
        $second = $this->lecturers->get(($offset + 1) % $count);

        if ($first->id === $second->id) {
            $second = $this->lecturers->get(($offset + 2) % $count);
        }

        return [$first, $second];
    }

    private function seedReferences(
        NuirSubmission $submission,
        int $total,
        int $approved,
        int $rejected = 0,
    ): void {
        for ($order = 1; $order <= $total; $order++) {
            $refApproved = null;
            $refNote = null;

            if ($order <= $approved) {
                $refApproved = true;
            } elseif ($order <= $approved + $rejected) {
                $refApproved = false;
                $refNote = 'Referensi perlu diperbaiki (simulasi seeder).';
            }

            NuirReference::create([
                'nuir_submission_id' => $submission->id,
                'ref_order' => $order,
                'link_ojs' => "https://journal.example.test/ojs/{$submission->id}/{$order}",
                'indexer_name' => 'SINTA',
                'link_index' => "https://sinta.example.test/detail/{$order}",
                'link_drive' => "https://drive.example.test/file/{$submission->id}-{$order}",
                'quote' => "Kutipan referensi ke-{$order} untuk {$submission->title}.",
                'relevance' => "Relevansi referensi ke-{$order} terhadap topik penelitian.",
                'ref_approved' => $refApproved,
                'ref_note' => $refNote,
            ]);
        }
    }
}
