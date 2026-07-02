<?php

namespace Database\Seeders;

use App\Models\GuideAllocation;
use App\Models\GuideExaminer;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirRevisionEvent;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirService;
use App\Support\NuirTextLimits;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class NuirSeeder extends Seeder
{
    private const LEGACY_YEAR = '2021';

    private const MIN_REFS = 10;

    private const MIN_NUI_WORDS = 12;

    private const MIN_TITLE_WORDS = 3;

    private const MAX_TITLE_WORDS = 20;

    private const MAX_NUI_WORDS = 300;

    private string $year = self::LEGACY_YEAR;

    private User $dbs;

    /** @var Collection<int, User> */
    private Collection $lecturers;

    private ?User $pembimbing1 = null;

    private ?User $pembimbing2 = null;

    private ?User $penguji1 = null;

    private ?User $penguji2 = null;

    private ?User $penguji3 = null;

    private ?User $manajer = null;

    private ?User $validator = null;

    private bool $simulationMode = false;

    public function run(): void
    {
        $this->simulationMode = User::where('username', 'mahasiswa1')->exists();
        $this->year = $this->simulationMode
            ? NuirSimulationAccountSeeder::SIMULATION_YEAR
            : self::LEGACY_YEAR;

        $dbs = User::where('username', 'dbs')->role('dbs')->first()
            ?? User::role('dbs')->first();

        if (! $dbs) {
            $this->command?->warn('NuirSeeder: butuh akun DBS, dilewati.');

            return;
        }

        if ($this->simulationMode) {
            $students = $this->simulationStudents();
            $this->loadSimulationGuides();
        } else {
            $students = User::role('mahasiswa')->orderBy('id')->skip(8)->take(8)->get();
            $this->lecturers = User::role('dosen')->get();

            if ($this->lecturers->count() < 4) {
                $this->command?->warn('NuirSeeder: butuh minimal 4 dosen, dilewati.');

                return;
            }
        }

        if ($students->count() < 8) {
            $this->command?->warn('NuirSeeder: butuh 8 mahasiswa simulasi, dilewati.');

            return;
        }

        $this->dbs = $dbs;
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

        $this->seedSettings();
        $this->seedDraftSubmission($draftStudent);
        $this->seedSubmittedSubmission($submittedStudent);
        $this->seedSubmittedReadyForReview($reviewReadyStudent);
        $this->seedRevisionChain($revisionStudent);
        $this->seedContentOkWithPendingProposal($proposalPendingStudent);
        $this->seedContentOkWithPartialAcceptance($proposalPartialStudent);
        $this->seedContentOkWithRetriedProposal($proposalRetriedStudent);
        $this->seedFinalizedFlow($finalizedStudent);

        if ($this->simulationMode) {
            $this->seedStudentWithoutNuirSubmission();
            $this->seedValidatorAssignments();
            $this->seedSimulationEnrichment();
        }

        $this->command?->info(sprintf(
            'NuirSeeder: %d setting, %d submission, %d referensi, %d proposal untuk angkatan %s%s.',
            NuirSetting::count(),
            NuirSubmission::count(),
            NuirReference::count(),
            NuirProposal::count(),
            $this->year,
            $this->simulationMode ? ' (mode simulasi)' : '',
        ));
    }

    private function simulationStudents(): Collection
    {
        return collect(range(1, 8))
            ->map(fn (int $number) => User::where('username', "mahasiswa{$number}")->first())
            ->filter()
            ->values();
    }

    /**
     * Mahasiswa simulasi tanpa submission NUIR — untuk uji alur pengisian NUI dari form kosong.
     */
    private function seedStudentWithoutNuirSubmission(): void
    {
        $student = User::where('username', NuirSimulationAccountSeeder::EMPTY_NUIR_STUDENT_USERNAME)->first();

        if (! $student) {
            $this->command?->warn('NuirSeeder: akun '.NuirSimulationAccountSeeder::EMPTY_NUIR_STUDENT_USERNAME.' tidak ditemukan, dilewati.');

            return;
        }

        NuirSubmission::where('user_id', $student->id)->delete();

        $this->ensureGuideExaminer($student);

        $this->command?->info(sprintf(
            'NuirSeeder: %s siap tanpa pengajuan NUIR (password: %s).',
            $student->username,
            NuirSimulationAccountSeeder::PASSWORD,
        ));
    }

    private function loadSimulationGuides(): void
    {
        $this->pembimbing1 = User::where('username', 'pembimbing1')->first();
        $this->pembimbing2 = User::where('username', 'pembimbing2')->first();
        $this->penguji1 = User::where('username', 'penguji1')->first();
        $this->penguji2 = User::where('username', 'penguji2')->first();
        $this->penguji3 = User::where('username', 'penguji3')->first();
        $this->manajer = User::where('username', 'manajer1')->first();
        $this->validator = User::where('username', 'validator1')->first();

        $this->lecturers = collect([
            $this->pembimbing1,
            $this->pembimbing2,
            $this->penguji1,
            $this->penguji2,
            $this->penguji3,
        ])->filter()->values();
    }

    private function seedSettings(): void
    {
        $wordLimits = [
            'max_references' => self::MIN_REFS,
            'min_words_title' => self::MIN_TITLE_WORDS,
            'max_words_title' => self::MAX_TITLE_WORDS,
            'min_words_novelty' => self::MIN_NUI_WORDS,
            'max_words_novelty' => self::MAX_NUI_WORDS,
            'min_words_urgency' => self::MIN_NUI_WORDS,
            'max_words_urgency' => self::MAX_NUI_WORDS,
            'min_words_impact' => self::MIN_NUI_WORDS,
            'max_words_impact' => self::MAX_NUI_WORDS,
            'max_chars_novelty' => null,
            'max_chars_urgency' => null,
            'max_chars_impact' => null,
        ];

        NuirSetting::updateOrCreate(
            ['year_generation' => $this->year],
            array_merge([
                'stage' => 1,
                'active' => true,
                'deadline' => now()->addMonths(2)->toDateString(),
                'min_references_approved' => self::MIN_REFS,
            ], $wordLimits),
        );

        if (! $this->simulationMode) {
            NuirSetting::updateOrCreate(
                ['year_generation' => self::LEGACY_YEAR],
                array_merge([
                    'stage' => 1,
                    'active' => true,
                    'deadline' => now()->addMonths(2)->toDateString(),
                    'min_references_approved' => self::MIN_REFS,
                ], $wordLimits),
            );
        }

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
        $attributes = [
            'year_generation' => $this->year,
            'examiner1_id' => null,
            'examiner2_id' => null,
            'examiner3_id' => null,
            'guide1_id' => null,
            'guide2_id' => null,
            'chief_id' => null,
        ];

        if ($this->simulationMode) {
            $attributes['examiner1_id'] = $this->penguji1?->id;
            $attributes['examiner2_id'] = $this->penguji2?->id;
            $attributes['examiner3_id'] = $this->penguji3?->id;
            $attributes['chief_id'] = $this->penguji1?->id;
        }

        return GuideExaminer::updateOrCreate(['user_id' => $student->id], $attributes);
    }

    private function seedDraftSubmission(User $student): void
    {
        NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => $this->year,
            'title' => $this->titleText('Judul awal workspace — '.$student->name),
            'novelty' => null,
            'urgency' => null,
            'impact' => null,
            'status' => 'title_slot',
            'title_saved_at' => now(),
        ]);
    }

    private function seedSubmittedSubmission(User $student): void
    {
        $submission = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => $this->year,
            'title' => $this->titleText('Submitted NUIR — '.$student->name),
            'novelty' => $this->nuiText('Novelty submitted menunggu review validator dan pembimbing'),
            'urgency' => $this->nuiText('Urgency submitted menunggu review validator dan pembimbing'),
            'impact' => $this->nuiText('Impact submitted menunggu review validator dan pembimbing'),
            'status' => 'submitted',
            'title_saved_at' => now(),
            'novelty_saved_at' => now(),
            'urgency_saved_at' => now(),
            'impact_saved_at' => now(),
        ]);

        $this->seedReferences($submission, total: self::MIN_REFS, approved: 5, rejected: 2);

        if ($this->simulationMode && $this->pembimbing1 && $this->pembimbing2) {
            NuirProposal::create([
                'nuir_submission_id' => $submission->id,
                'guide1_id' => $this->pembimbing1->id,
                'guide2_id' => $this->pembimbing2->id,
            ]);
        }
    }

    private function seedSubmittedReadyForReview(User $student): void
    {
        $submission = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => $this->year,
            'title' => $this->titleText('Menunggu Review Pembimbing — '.$student->name),
            'novelty' => $this->nuiText('Novelty menunggu review pembimbing setelah seluruh referensi lolos validasi'),
            'urgency' => $this->nuiText('Urgency menunggu review pembimbing setelah seluruh referensi lolos validasi'),
            'impact' => $this->nuiText('Impact menunggu review pembimbing setelah seluruh referensi lolos validasi'),
            'status' => 'submitted',
            'title_saved_at' => now(),
            'novelty_saved_at' => now(),
            'urgency_saved_at' => now(),
            'impact_saved_at' => now(),
        ]);

        $this->seedReferences($submission, total: self::MIN_REFS, approved: self::MIN_REFS, rejected: 0);
    }

    private function seedRevisionChain(User $student): void
    {
        // In simulation mode: demonstrate guide NUI revision (inline edit, no DBS, no v2 chain)
        if ($this->simulationMode) {
            $submission = NuirSubmission::create([
                'user_id' => $student->id,
                'year_generation' => $this->year,
                'title' => $this->titleText('Revisi NUI oleh Pembimbing — '.$student->name),
                'novelty' => $this->nuiText('Novelty yang diminta revisi Pembimbing 1 karena kurang spesifik'),
                'urgency' => $this->nuiText('Urgency sudah disetujui kedua pembimbing dan layak dilanjutkan'),
                'impact' => $this->nuiText('Impact yang diminta revisi Pembimbing 2 karena indikator belum terukur'),
                'status' => 'submitted',
                'title_saved_at' => now()->subDays(10),
                'novelty_saved_at' => now()->subDays(10),
                'urgency_saved_at' => now()->subDays(10),
                'impact_saved_at' => now()->subDays(10),
            ]);

            $this->seedReferences($submission, total: self::MIN_REFS, approved: 4, rejected: 3);

            // Update rejected references with specific validator notes and revision fields
            $rejectionDetails = [
                5 => ['ref_note' => 'Link OJS tidak dapat diakses, halaman 404.', 'ref_revision_fields' => ['link_ojs']],
                6 => ['ref_note' => 'Jurnal ini terindeks Scopus, bukan SINTA — perbaiki nama indexer dan link index.', 'ref_revision_fields' => ['indexer_name', 'link_index']],
                7 => ['ref_note' => 'Kutipan tidak relevan dengan variabel penelitian. Perbaiki kutipan dan uraian relevansi.', 'ref_revision_fields' => ['quote', 'relevance']],
            ];

            $submission->references()->where('ref_approved', false)->get()
                ->each(function (NuirReference $ref) use ($rejectionDetails): void {
                    if (isset($rejectionDetails[$ref->ref_order])) {
                        $ref->update($rejectionDetails[$ref->ref_order]);
                    }
                });

            if ($this->pembimbing1 && $this->pembimbing2) {
                NuirProposal::create([
                    'nuir_submission_id' => $submission->id,
                    'guide1_id' => $this->pembimbing1->id,
                    'guide2_id' => $this->pembimbing2->id,
                ]);
            }

            return;
        }

        // Legacy (non-simulation) mode: DBS-triggered revision chain with v1 → v2
        $versionOne = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => $this->year,
            'title' => $this->titleText('Revisi v1 — '.$student->name),
            'novelty' => $this->nuiText('Novelty versi 1 yang diminta revisi manajer'),
            'urgency' => $this->nuiText('Urgency versi 1 yang diminta revisi manajer'),
            'impact' => $this->nuiText('Impact versi 1 yang diminta revisi manajer'),
            'status' => 'revision',
            'dbs_note' => 'Perbaiki referensi SINTA dan perjelas urgensi penelitian.',
            'dbs_reviewer_id' => $this->dbs->id,
            'dbs_reviewed_at' => now()->subDays(3),
            'title_saved_at' => now()->subDays(10),
            'novelty_saved_at' => now()->subDays(10),
            'urgency_saved_at' => now()->subDays(10),
            'impact_saved_at' => now()->subDays(10),
        ]);

        $this->seedReferences($versionOne, total: self::MIN_REFS, approved: 4, rejected: 3);

        NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => $this->year,
            'parent_submission_id' => $versionOne->id,
            'version' => 2,
            'title' => $this->titleText('Monitoring Kualitas Udara IoT-ML — Revisi v2 '.$student->name),
            'novelty' => $this->nuiText('Novelty versi 2 hasil perbaikan mahasiswa setelah masukan manajer'),
            'urgency' => $this->nuiText('Urgency versi 2 hasil perbaikan mahasiswa dengan penekanan kondisi kesehatan masyarakat'),
            'impact' => $this->nuiText('Impact versi 2 hasil perbaikan mahasiswa mencakup manfaat kebijakan publik'),
            'status' => 'draft',
            'title_saved_at' => now()->subDays(2),
            'novelty_saved_at' => now()->subDays(2),
            'urgency_saved_at' => now()->subDays(2),
            'impact_saved_at' => now()->subDays(2),
        ]);
    }

    private function seedContentOkWithPendingProposal(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Proposal Pending');
        [$guide1, $guide2] = $this->pickGuidePair(0);

        $proposal = NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
        ]);

        $this->seedProposalSelectionEvents($submission, $proposal, $student, $guide1, $guide2, now()->subDays(3));
    }

    private function seedContentOkWithPartialAcceptance(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Proposal Sebagian Diterima');
        [$guide1, $guide2] = $this->pickGuidePair(0);

        $proposal = NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
            'guide1_status' => 'accepted',
            'guide1_responded_at' => now()->subDay(),
        ]);

        $this->seedProposalSelectionEvents($submission, $proposal, $student, $guide1, $guide2, now()->subDays(3));
    }

    private function seedContentOkWithRetriedProposal(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Proposal Ulang');
        [$rejectedGuide1, $rejectedGuide2] = $this->pickGuidePair(2);
        [$pendingGuide1, $pendingGuide2] = $this->pickGuidePair(0);

        $oldProposal = NuirProposal::create([
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

        // Selection events for original (rejected) proposal
        $this->seedProposalSelectionEvents($submission, $oldProposal, $student, $rejectedGuide1, $rejectedGuide2, now()->subDays(7));

        // Rejection events for original guides
        NuirRevisionEvent::updateOrCreate(
            ['nuir_submission_id' => $submission->id, 'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_REJECTION, 'subject' => 'guide1', 'nuir_proposal_id' => $oldProposal->id],
            ['submission_version' => $submission->version ?? 1, 'actor_id' => $rejectedGuide1->id, 'actor_role' => NuirRevisionEvent::ROLE_GUIDE1, 'note' => 'Kuota bimbingan penuh.', 'recorded_at' => now()->subDays(5)],
        );
        NuirRevisionEvent::updateOrCreate(
            ['nuir_submission_id' => $submission->id, 'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_REJECTION, 'subject' => 'guide2', 'nuir_proposal_id' => $oldProposal->id],
            ['submission_version' => $submission->version ?? 1, 'actor_id' => $rejectedGuide2->id, 'actor_role' => NuirRevisionEvent::ROLE_GUIDE2, 'note' => 'Tidak sesuai minat bimbingan.', 'recorded_at' => now()->subDays(4)],
        );

        $newProposal = NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $pendingGuide1->id,
            'guide2_id' => $pendingGuide2->id,
        ]);

        // Selection events for re-proposed guides
        $this->seedProposalSelectionEvents($submission, $newProposal, $student, $pendingGuide1, $pendingGuide2, now()->subDays(3));
    }

    private function seedFinalizedFlow(User $student): void
    {
        $submission = $this->createContentOkSubmission($student, 'Finalized');
        [$guide1, $guide2] = $this->pickGuidePair(0);

        $proposal = NuirProposal::create([
            'nuir_submission_id' => $submission->id,
            'guide1_id' => $guide1->id,
            'guide2_id' => $guide2->id,
            'guide1_status' => 'accepted',
            'guide2_status' => 'accepted',
            'guide1_responded_at' => now()->subDays(2),
            'guide2_responded_at' => now()->subDay(),
        ]);

        $this->seedProposalSelectionEvents($submission, $proposal, $student, $guide1, $guide2, now()->subDays(5));

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
            'year_generation' => $this->year,
            'title' => $this->titleText("{$label} — {$student->name}"),
            'novelty' => $this->nuiText("Novelty {$label} simulasi alur usulan calon pembimbing per kursi"),
            'urgency' => $this->nuiText("Urgency {$label} simulasi alur usulan calon pembimbing per kursi"),
            'impact' => $this->nuiText("Impact {$label} simulasi alur usulan calon pembimbing per kursi"),
            'status' => 'content_ok',
            'title_saved_at' => now()->subWeek(),
            'novelty_saved_at' => now()->subWeek(),
            'urgency_saved_at' => now()->subWeek(),
            'impact_saved_at' => now()->subWeek(),
        ]);

        $this->seedReferences($submission, total: self::MIN_REFS, approved: self::MIN_REFS, rejected: 0);

        return $submission;
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function pickGuidePair(int $offset): array
    {
        if ($this->simulationMode) {
            return match ($offset) {
                2 => [$this->penguji1, $this->penguji2],
                default => [$this->pembimbing1, $this->pembimbing2],
            };
        }

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

    private function seedValidatorAssignments(): void
    {
        if (! $this->manajer || ! $this->validator) {
            return;
        }

        NuirSubmission::query()
            ->where('year_generation', $this->year)
            ->where('status', '!=', 'draft')
            ->each(function (NuirSubmission $submission): void {
                \App\Models\NuirAssignment::updateOrCreate(
                    ['nuir_submission_id' => $submission->id],
                    [
                        'validator_id' => $this->validator->id,
                        'assigned_by' => $this->manajer->id,
                        'assigned_at' => now(),
                    ],
                );
            });
    }

    private function seedSimulationEnrichment(): void
    {
        $this->seedSimulationGuideAllocations();
        $this->seedSimulationContentReviews();
        $this->seedSimulationRevisionHistory();
        $this->seedManajerRevisionDemo();
        $this->seedProposalCancellationDemo();
        $this->syncSimulationQuotaFilled();
    }

    private function seedSimulationGuideAllocations(): void
    {
        $year = (int) $this->year;

        $quotas = [
            'pembimbing1' => ['guide1_quota' => 10, 'guide2_quota' => 10],
            'pembimbing2' => ['guide1_quota' => 10, 'guide2_quota' => 10],
            'penguji1' => ['guide1_quota' => 5, 'guide2_quota' => 5],
            'penguji2' => ['guide1_quota' => 5, 'guide2_quota' => 5],
            'penguji3' => ['guide1_quota' => 0, 'guide2_quota' => 5],
        ];

        foreach ($quotas as $username => $values) {
            $user = User::where('username', $username)->first();

            if (! $user) {
                continue;
            }

            GuideAllocation::updateOrCreate(
                ['user_id' => $user->id, 'year' => $year],
                [
                    'guide1_quota' => $values['guide1_quota'],
                    'guide2_quota' => $values['guide2_quota'],
                    'guide1_filled' => 0,
                    'guide2_filled' => 0,
                    'active' => true,
                ],
            );
        }
    }

    private function syncSimulationQuotaFilled(): void
    {
        $year = (int) $this->year;

        GuideAllocation::query()
            ->where('year', $year)
            ->whereIn('user_id', $this->lecturers->pluck('id'))
            ->update(['guide1_filled' => 0, 'guide2_filled' => 0]);

        NuirProposal::query()
            ->with('submission')
            ->whereHas('submission', fn ($query) => $query->where('year_generation', $this->year))
            ->get()
            ->each(function (NuirProposal $proposal) use ($year): void {
                if ($proposal->guide1_id && in_array($proposal->guide1_status, ['pending', 'accepted'], true)) {
                    GuideAllocation::query()
                        ->where('user_id', $proposal->guide1_id)
                        ->where('year', $year)
                        ->increment('guide1_filled');
                }

                if ($proposal->guide2_id && in_array($proposal->guide2_status, ['pending', 'accepted'], true)) {
                    GuideAllocation::query()
                        ->where('user_id', $proposal->guide2_id)
                        ->where('year', $year)
                        ->increment('guide2_filled');
                }
            });
    }

    private function seedSimulationContentReviews(): void
    {
        if (! $this->pembimbing1 || ! $this->pembimbing2) {
            return;
        }

        // mahasiswa2 (submitted + proposal): novelty+urgency approved by both, impact rejected by both
        $submittedWithProposal = $this->latestSubmissionFor('mahasiswa2', 'submitted');
        if ($submittedWithProposal) {
            foreach (['novelty', 'urgency'] as $field) {
                foreach ([
                    [$this->pembimbing1, NuirContentReview::ROLE_GUIDE1],
                    [$this->pembimbing2, NuirContentReview::ROLE_GUIDE2],
                ] as [$guide, $role]) {
                    NuirContentReview::updateOrCreate(
                        ['nuir_submission_id' => $submittedWithProposal->id, 'user_id' => $guide->id, 'field' => $field],
                        ['role' => $role, 'approved' => true, 'note' => null, 'reviewed_at' => now()->subDays(2)],
                    );
                }
            }

            foreach ([
                [$this->pembimbing1, NuirContentReview::ROLE_GUIDE1, 'Uraikan manfaat praktis bagi pemangku kebijakan daerah.'],
                [$this->pembimbing2, NuirContentReview::ROLE_GUIDE2, 'Tambahkan indikator dampak jangka panjang yang dapat diukur.'],
            ] as [$guide, $role, $note]) {
                NuirContentReview::updateOrCreate(
                    ['nuir_submission_id' => $submittedWithProposal->id, 'user_id' => $guide->id, 'field' => NuirContentReview::FIELD_IMPACT],
                    ['role' => $role, 'approved' => false, 'note' => $note, 'reviewed_at' => now()->subDay()],
                );
            }
        }

        // mahasiswa4 (submitted, NUI revision by guides): P1 rejects novelty, P2 rejects impact
        $nuiRevisionSubmission = $this->latestSubmissionFor('mahasiswa4', 'submitted');
        if ($nuiRevisionSubmission) {
            foreach ([
                [$this->pembimbing1, NuirContentReview::ROLE_GUIDE1, 'novelty', false, 'Kebaruan penelitian perlu lebih spesifik — bandingkan dengan literatur terkini.'],
                [$this->pembimbing1, NuirContentReview::ROLE_GUIDE1, 'urgency', true, null],
                [$this->pembimbing1, NuirContentReview::ROLE_GUIDE1, 'impact', true, null],
                [$this->pembimbing2, NuirContentReview::ROLE_GUIDE2, 'novelty', true, null],
                [$this->pembimbing2, NuirContentReview::ROLE_GUIDE2, 'urgency', true, null],
                [$this->pembimbing2, NuirContentReview::ROLE_GUIDE2, 'impact', false, 'Indikator dampak belum terukur — sertakan metrik kuantitatif yang spesifik.'],
            ] as [$guide, $role, $field, $approved, $note]) {
                NuirContentReview::updateOrCreate(
                    ['nuir_submission_id' => $nuiRevisionSubmission->id, 'user_id' => $guide->id, 'field' => $field],
                    ['role' => $role, 'approved' => $approved, 'note' => $note, 'reviewed_at' => now()->subDay()],
                );
            }
        }

        // mahasiswa5, mahasiswa7 (content_ok via guide approval): both guides approve all NUI fields
        foreach (['mahasiswa5', 'mahasiswa7'] as $username) {
            $submission = $this->latestSubmissionFor($username, 'content_ok');
            if ($submission) {
                $this->seedFullGuideApprovals($submission, daysAgo: 7);
            }
        }

        // mahasiswa6 (content_ok, partial proposal acceptance): both guides approve all NUI fields
        $partialSubmission = $this->latestSubmissionFor('mahasiswa6', 'content_ok');
        if ($partialSubmission) {
            // guide1: all fields approved
            foreach (NuirContentReview::FIELDS as $field) {
                NuirContentReview::updateOrCreate(
                    ['nuir_submission_id' => $partialSubmission->id, 'user_id' => $this->pembimbing1->id, 'field' => $field],
                    ['role' => NuirContentReview::ROLE_GUIDE1, 'approved' => true, 'note' => null, 'reviewed_at' => now()->subDay()],
                );
            }
            // guide2: all fields approved (content_ok requires both)
            foreach (NuirContentReview::FIELDS as $field) {
                NuirContentReview::updateOrCreate(
                    ['nuir_submission_id' => $partialSubmission->id, 'user_id' => $this->pembimbing2->id, 'field' => $field],
                    ['role' => NuirContentReview::ROLE_GUIDE2, 'approved' => true, 'note' => null, 'reviewed_at' => now()->subHours(12)],
                );
            }
        }

        // mahasiswa8 (finalized): both guides approve all NUI fields
        $finalizedSubmission = $this->latestSubmissionFor('mahasiswa8', 'finalized');
        if ($finalizedSubmission) {
            $this->seedFullGuideApprovals($finalizedSubmission, daysAgo: 3);
        }
    }

    private function seedFullGuideApprovals(NuirSubmission $submission, int $daysAgo = 7): void
    {
        foreach ([
            [$this->pembimbing1, NuirContentReview::ROLE_GUIDE1],
            [$this->pembimbing2, NuirContentReview::ROLE_GUIDE2],
        ] as [$guide, $role]) {
            foreach (NuirContentReview::FIELDS as $field) {
                NuirContentReview::updateOrCreate(
                    ['nuir_submission_id' => $submission->id, 'user_id' => $guide->id, 'field' => $field],
                    ['role' => $role, 'approved' => true, 'note' => null, 'reviewed_at' => now()->subDays($daysAgo)],
                );
            }
        }
    }

    private function seedSimulationRevisionHistory(): void
    {
        $submitted = $this->latestSubmissionFor('mahasiswa2', 'submitted');
        if ($submitted && $this->validator) {
            $submitted->references()
                ->where('ref_approved', false)
                ->each(function (NuirReference $reference) use ($submitted): void {
                    NuirRevisionEvent::updateOrCreate(
                        [
                            'nuir_submission_id' => $reference->nuir_submission_id,
                            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
                            'subject' => (string) $reference->ref_order,
                            'ref_order' => $reference->ref_order,
                            'actor_id' => $this->validator->id,
                        ],
                        [
                            'submission_version' => $submitted->version ?? 1,
                            'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
                            'note' => $reference->ref_note ?? 'Referensi perlu diperbaiki (simulasi seeder).',
                            'recorded_at' => now()->subDays(2),
                        ],
                    );
                });
        }

        // mahasiswa4 (submitted, revisi NUI + referensi): log guide NUI revision events + reference rejection events
        $nuiRevisionSubmission = $this->latestSubmissionFor('mahasiswa4', 'submitted');
        if ($nuiRevisionSubmission && $this->pembimbing1 && $this->pembimbing2) {
            foreach ([
                ['field' => 'novelty', 'actor' => $this->pembimbing1, 'role' => NuirRevisionEvent::ROLE_GUIDE1, 'note' => 'Kebaruan penelitian perlu lebih spesifik — bandingkan dengan literatur terkini.'],
                ['field' => 'impact', 'actor' => $this->pembimbing2, 'role' => NuirRevisionEvent::ROLE_GUIDE2, 'note' => 'Indikator dampak belum terukur — sertakan metrik kuantitatif yang spesifik.'],
            ] as $revision) {
                NuirRevisionEvent::updateOrCreate(
                    [
                        'nuir_submission_id' => $nuiRevisionSubmission->id,
                        'event_type' => NuirRevisionEvent::TYPE_NUI_REVISION,
                        'subject' => $revision['field'],
                        'actor_id' => $revision['actor']->id,
                    ],
                    [
                        'submission_version' => $nuiRevisionSubmission->version ?? 1,
                        'actor_role' => $revision['role'],
                        'note' => $revision['note'],
                        'recorded_at' => now()->subDay(),
                    ],
                );
            }
        }

        if ($nuiRevisionSubmission && $this->validator) {
            $nuiRevisionSubmission->references()
                ->where('ref_approved', false)
                ->each(function (NuirReference $reference) use ($nuiRevisionSubmission): void {
                    NuirRevisionEvent::updateOrCreate(
                        [
                            'nuir_submission_id' => $nuiRevisionSubmission->id,
                            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
                            'subject' => (string) $reference->ref_order,
                            'ref_order' => $reference->ref_order,
                            'actor_id' => $this->validator->id,
                        ],
                        [
                            'submission_version' => $nuiRevisionSubmission->version ?? 1,
                            'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
                            'note' => $reference->ref_note ?? 'Referensi perlu diperbaiki.',
                            'recorded_at' => now()->subDays(2),
                        ],
                    );
                });
        }

        $retriedSubmission = $this->latestSubmissionFor('mahasiswa7', 'content_ok');
        if ($retriedSubmission) {
            $rejectedProposal = NuirProposal::query()
                ->where('nuir_submission_id', $retriedSubmission->id)
                ->where('guide1_status', 'rejected')
                ->where('guide2_status', 'rejected')
                ->first();

            if ($rejectedProposal) {
                foreach ([
                    [1, $rejectedProposal->guide1_id, $rejectedProposal->guide1_note],
                    [2, $rejectedProposal->guide2_id, $rejectedProposal->guide2_note],
                ] as [$guideOrder, $actorId, $note]) {
                    NuirRevisionEvent::updateOrCreate(
                        [
                            'nuir_submission_id' => $retriedSubmission->id,
                            'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_REJECTION,
                            'subject' => 'guide'.$guideOrder,
                            'nuir_proposal_id' => $rejectedProposal->id,
                            'actor_id' => $actorId,
                        ],
                        [
                            'submission_version' => $retriedSubmission->version ?? 1,
                            'actor_role' => $guideOrder === 1
                                ? NuirRevisionEvent::ROLE_GUIDE1
                                : NuirRevisionEvent::ROLE_GUIDE2,
                            'note' => $note ?? 'Usulan ditolak (simulasi seeder).',
                            'recorded_at' => $rejectedProposal->guide2_responded_at ?? now()->subDays(4),
                        ],
                    );
                }
            }
        }
    }

    private function seedManajerRevisionDemo(): void
    {
        if (! $this->validator || ! $this->pembimbing1 || ! $this->pembimbing2) {
            return;
        }

        $submission = $this->latestSubmissionFor('mahasiswa2', 'submitted');

        if ($submission) {
            $submission->update([
                'title' => $this->titleText('Integrasi IoT dan Machine Learning untuk Monitoring Kualitas Udara Perkotaan (Revisi Terakhir)'),
                'novelty' => $this->nuiText('Penelitian ini menawarkan kebaruan integrasi sensor IoT dengan deep learning untuk prediksi PM2.5 real-time di Indonesia'),
                'urgency' => $this->nuiText('Kualitas udara perkotaan memburuk dan masyarakat rentan membutuhkan peringatan dini'),
                'impact' => $this->nuiText('Dampak meliputi pengurangan paparan polusi dan rekomendasi kebijakan tata ruang hijau'),
            ]);

            foreach ([
                ['field' => 'impact', 'actor' => $this->pembimbing1, 'role' => NuirRevisionEvent::ROLE_GUIDE1, 'note' => 'Uraikan manfaat praktis bagi pemangku kebijakan daerah.'],
                ['field' => 'impact', 'actor' => $this->pembimbing2, 'role' => NuirRevisionEvent::ROLE_GUIDE2, 'note' => 'Tambahkan indikator dampak jangka panjang yang dapat diukur.'],
            ] as $revision) {
                NuirRevisionEvent::updateOrCreate(
                    [
                        'nuir_submission_id' => $submission->id,
                        'event_type' => NuirRevisionEvent::TYPE_NUI_REVISION,
                        'subject' => $revision['field'],
                        'actor_id' => $revision['actor']->id,
                    ],
                    [
                        'submission_version' => $submission->version ?? 1,
                        'actor_role' => $revision['role'],
                        'note' => $revision['note'],
                        'recorded_at' => now()->subDays(4),
                    ],
                );
            }

            $submission->references()->whereIn('ref_order', [6, 7, 8])->each(function (NuirReference $reference): void {
                if ($reference->ref_order === 8) {
                    NuirRevisionEvent::updateOrCreate(
                        [
                            'nuir_submission_id' => $reference->nuir_submission_id,
                            'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
                            'subject' => '8',
                            'ref_order' => 8,
                            'actor_id' => $this->validator->id,
                        ],
                        [
                            'submission_version' => 1,
                            'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
                            'note' => 'Kutipan belum menunjukkan relevansi langsung dengan variabel penelitian.',
                            'recorded_at' => now()->subDay(),
                        ],
                    );

                    return;
                }

                $reference->update([
                    'quote' => 'Kutipan referensi #'.$reference->ref_order.' setelah diperbaiki mahasiswa (versi terakhir).',
                    'ref_approved' => null,
                    'ref_note' => null,
                ]);
            });
        }

        $versionTwo = NuirSubmission::query()
            ->where('year_generation', $this->year)
            ->where('version', 2)
            ->whereHas('user', fn ($query) => $query->where('username', 'mahasiswa4'))
            ->first();

        if ($versionTwo) {
            foreach ([
                ['field' => 'novelty', 'actor' => $this->pembimbing2, 'role' => NuirRevisionEvent::ROLE_GUIDE2],
                ['field' => 'urgency', 'actor' => $this->pembimbing1, 'role' => NuirRevisionEvent::ROLE_GUIDE1],
            ] as $revision) {
                NuirRevisionEvent::updateOrCreate(
                    [
                        'nuir_submission_id' => $versionTwo->id,
                        'event_type' => NuirRevisionEvent::TYPE_NUI_REVISION,
                        'subject' => $revision['field'],
                        'actor_id' => $revision['actor']->id,
                    ],
                    [
                        'submission_version' => 2,
                        'actor_role' => $revision['role'],
                        'note' => 'Perbaiki '.ucfirst($revision['field']).' pada versi 2 sesuai masukan simulasi.',
                        'recorded_at' => now()->subDays(2),
                    ],
                );
            }

            $versionTwo->references()->where('ref_order', '<=', 3)->each(function (NuirReference $reference): void {
                NuirRevisionEvent::updateOrCreate(
                    [
                        'nuir_submission_id' => $reference->nuir_submission_id,
                        'event_type' => NuirRevisionEvent::TYPE_REFERENCE_REVISION,
                        'subject' => (string) $reference->ref_order,
                        'ref_order' => $reference->ref_order,
                        'actor_id' => $this->validator->id,
                    ],
                    [
                        'submission_version' => 2,
                        'actor_role' => NuirRevisionEvent::ROLE_VALIDATOR,
                        'note' => 'Referensi #'.$reference->ref_order.' perlu verifikasi link index pada versi 2.',
                        'recorded_at' => now()->subDays(3),
                    ],
                );
            });
        }
    }

    private function seedProposalCancellationDemo(): void
    {
        if (! $this->manajer || ! $this->pembimbing1 || ! $this->pembimbing2) {
            return;
        }

        // mahasiswa5 (content_ok, pending proposal): P2 dibatalkan oleh manajer
        $submission = $this->latestSubmissionFor('mahasiswa5', 'content_ok');

        if (! $submission) {
            return;
        }

        $proposal = NuirProposal::where('nuir_submission_id', $submission->id)
            ->where('final', false)
            ->latest('id')
            ->first();

        if (! $proposal || ! $proposal->guide2_id) {
            return;
        }

        $cancelledGuide2Id = $proposal->guide2_id;

        NuirRevisionEvent::updateOrCreate(
            [
                'nuir_submission_id' => $submission->id,
                'event_type'         => NuirRevisionEvent::TYPE_PROPOSAL_CANCELLATION,
                'subject'            => 'guide2',
                'nuir_proposal_id'   => $proposal->id,
                'actor_id'           => $this->manajer->id,
            ],
            [
                'submission_version' => $submission->version ?? 1,
                'target_user_id'     => $cancelledGuide2Id,
                'actor_role'         => NuirRevisionEvent::ROLE_MANAJER,
                'note'               => 'Kuota P2 dosen yang dipilih sudah habis berdasarkan sistem alokasi terbaru.',
                'recorded_at'        => now()->subDays(2),
            ],
        );

        $proposal->update([
            'guide2_id'           => null,
            'guide2_status'       => 'pending',
            'guide2_note'         => null,
            'guide2_responded_at' => null,
        ]);
    }

    private function latestSubmissionFor(string $username, string $status): ?NuirSubmission
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            return null;
        }

        return NuirSubmission::query()
            ->where('user_id', $user->id)
            ->where('year_generation', $this->year)
            ->where('status', $status)
            ->latest('id')
            ->first();
    }

    private function titleText(string $base): string
    {
        return $this->padWords($base, self::MIN_TITLE_WORDS);
    }

    private function seedProposalSelectionEvents(
        NuirSubmission $submission,
        NuirProposal $proposal,
        User $student,
        User $guide1,
        User $guide2,
        \Carbon\Carbon $at,
    ): void {
        NuirRevisionEvent::updateOrCreate(
            ['nuir_submission_id' => $submission->id, 'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_SELECTION, 'subject' => 'guide1', 'nuir_proposal_id' => $proposal->id, 'target_user_id' => $guide1->id],
            ['submission_version' => $submission->version ?? 1, 'actor_id' => $student->id, 'actor_role' => NuirRevisionEvent::ROLE_MAHASISWA, 'note' => '', 'recorded_at' => $at],
        );
        NuirRevisionEvent::updateOrCreate(
            ['nuir_submission_id' => $submission->id, 'event_type' => NuirRevisionEvent::TYPE_PROPOSAL_SELECTION, 'subject' => 'guide2', 'nuir_proposal_id' => $proposal->id, 'target_user_id' => $guide2->id],
            ['submission_version' => $submission->version ?? 1, 'actor_id' => $student->id, 'actor_role' => NuirRevisionEvent::ROLE_MAHASISWA, 'note' => '', 'recorded_at' => $at],
        );
    }

    private function nuiText(string $base): string
    {
        return $this->padWords($base, self::MIN_NUI_WORDS);
    }

    private function padWords(string $base, int $minWords): string
    {
        $text = trim($base);

        while (NuirTextLimits::wordCount($text) < $minWords) {
            $text .= ' Konten simulasi.';
        }

        return $text;
    }
}
