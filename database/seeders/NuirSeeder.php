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
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class NuirSeeder extends Seeder
{
    private const LEGACY_YEAR = '2021';

    private const MIN_REFS = 10;

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
        NuirSetting::updateOrCreate(
            ['year_generation' => $this->year],
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

        if (! $this->simulationMode) {
            NuirSetting::updateOrCreate(
                ['year_generation' => self::LEGACY_YEAR],
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

        return GuideExaminer::firstOrCreate(['user_id' => $student->id], $attributes);
    }

    private function seedDraftSubmission(User $student): void
    {
        $submission = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => $this->year,
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
            'year_generation' => $this->year,
            'title' => 'Submitted NUIR — '.$student->name,
            'novelty' => 'Novelty submitted simulasi menunggu review DBS.',
            'urgency' => 'Urgency submitted simulasi menunggu review DBS.',
            'impact' => 'Impact submitted simulasi menunggu review DBS.',
            'status' => 'submitted',
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
            'year_generation' => $this->year,
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

        if ($this->simulationMode && $this->pembimbing1 && $this->pembimbing2) {
            NuirProposal::create([
                'nuir_submission_id' => $versionOne->id,
                'guide1_id' => $this->pembimbing1->id,
                'guide2_id' => $this->pembimbing2->id,
            ]);
        }

        $versionTwo = NuirSubmission::create([
            'user_id' => $student->id,
            'year_generation' => $this->year,
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
        [$guide1, $guide2] = $this->pickGuidePair(0);

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
        [$pendingGuide1, $pendingGuide2] = $this->pickGuidePair(0);

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
            'title' => "{$label} — {$student->name}",
            'novelty' => "Novelty {$label} simulasi alur usulan calon pembimbing.",
            'urgency' => "Urgency {$label} simulasi alur usulan calon pembimbing.",
            'impact' => "Impact {$label} simulasi alur usulan calon pembimbing.",
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
                if (in_array($proposal->guide1_status, ['pending', 'accepted'], true)) {
                    GuideAllocation::query()
                        ->where('user_id', $proposal->guide1_id)
                        ->where('year', $year)
                        ->increment('guide1_filled');
                }

                if (in_array($proposal->guide2_status, ['pending', 'accepted'], true)) {
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

        $partialSubmission = $this->latestSubmissionFor('mahasiswa6', 'content_ok');
        if ($partialSubmission) {
            foreach (NuirContentReview::FIELDS as $field) {
                NuirContentReview::updateOrCreate(
                    [
                        'nuir_submission_id' => $partialSubmission->id,
                        'user_id' => $this->pembimbing1->id,
                        'field' => $field,
                    ],
                    [
                        'role' => NuirContentReview::ROLE_GUIDE1,
                        'approved' => true,
                        'note' => null,
                        'reviewed_at' => now()->subDay(),
                    ],
                );
            }

            NuirContentReview::updateOrCreate(
                [
                    'nuir_submission_id' => $partialSubmission->id,
                    'user_id' => $this->pembimbing2->id,
                    'field' => NuirContentReview::FIELD_NOVELTY,
                ],
                [
                    'role' => NuirContentReview::ROLE_GUIDE2,
                    'approved' => true,
                    'note' => null,
                    'reviewed_at' => now()->subHours(12),
                ],
            );
        }

        $finalizedSubmission = $this->latestSubmissionFor('mahasiswa8', 'finalized');
        if ($finalizedSubmission) {
            foreach ([
                [$this->pembimbing1, NuirContentReview::ROLE_GUIDE1],
                [$this->pembimbing2, NuirContentReview::ROLE_GUIDE2],
            ] as [$guide, $role]) {
                foreach (NuirContentReview::FIELDS as $field) {
                    NuirContentReview::updateOrCreate(
                        [
                            'nuir_submission_id' => $finalizedSubmission->id,
                            'user_id' => $guide->id,
                            'field' => $field,
                        ],
                        [
                            'role' => $role,
                            'approved' => true,
                            'note' => null,
                            'reviewed_at' => now()->subDays(3),
                        ],
                    );
                }
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

        $revisionParent = NuirSubmission::query()
            ->where('year_generation', $this->year)
            ->where('status', 'revision')
            ->whereHas('user', fn ($query) => $query->where('username', 'mahasiswa4'))
            ->first();

        if ($revisionParent && $this->dbs) {
            NuirRevisionEvent::updateOrCreate(
                [
                    'nuir_submission_id' => $revisionParent->id,
                    'event_type' => NuirRevisionEvent::TYPE_DBS_REVISION,
                    'subject' => 'submission',
                    'actor_id' => $this->dbs->id,
                ],
                [
                    'submission_version' => $revisionParent->version ?? 1,
                    'actor_role' => NuirRevisionEvent::ROLE_DBS,
                    'note' => $revisionParent->dbs_note ?? 'Perbaiki referensi SINTA dan perjelas urgensi penelitian.',
                    'recorded_at' => $revisionParent->dbs_reviewed_at ?? now()->subDays(3),
                ],
            );
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
}
