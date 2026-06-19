<?php

namespace App\Livewire;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Services\Examination\ExamRegistrationExaminerSync;
use App\Services\Examination\ExamScoreUpdater;
use App\Services\Examination\ScoringFormPresenter;
use App\Support\ExaminerSlotSelectOptions;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Livewire\Component;

class ExamScoresDetail extends Component
{
    public int $recordId;

    public ?int $replacingScoreId = null;

    public ?int $newExaminerId = null;

    public ?int $editingScoreId = null;

    public ?string $editingGrade = null;

    public ?int $expandedScoreActionsId = null;

    public function toggleScoreActions(int $scoreId): void
    {
        $this->expandedScoreActionsId = $this->expandedScoreActionsId === $scoreId
            ? null
            : $scoreId;
    }

    public function closeScoreActions(): void
    {
        $this->expandedScoreActionsId = null;
    }

    public function openReplaceModal(int $scoreId): void
    {
        $this->closeGradeEdit();
        $this->closeScoreActions();
        $this->replacingScoreId = $scoreId;
        $this->newExaminerId = null;
        $this->resetErrorBag();
    }

    public function closeReplaceModal(): void
    {
        $this->replacingScoreId = null;
        $this->newExaminerId = null;
        $this->resetErrorBag();
    }

    public function replaceExaminer(ExamRegistrationExaminerSync $sync): void
    {
        $this->validate([
            'newExaminerId' => ['required', 'integer', 'exists:users,id'],
        ]);

        $record = ExamRegistration::findOrFail($this->recordId);
        $score = ExamScore::query()
            ->where('exam_registration_id', $record->id)
            ->findOrFail($this->replacingScoreId);

        if ((int) $this->newExaminerId === (int) $score->user_id) {
            $this->addError('newExaminerId', 'Pengganti harus berbeda dari penguji saat ini.');

            return;
        }

        if (in_array((int) $this->newExaminerId, ExaminerSlotSelectOptions::assignedIdsFromRegistration($record, (int) $score->user_id), true)) {
            $this->addError('newExaminerId', 'Dosen ini sudah terdaftar sebagai penguji/pembimbing lain pada ujian ini.');

            return;
        }

        $sync->replaceExaminer($record, $score, (int) $this->newExaminerId);

        Notification::make()
            ->success()
            ->title('Penguji berhasil diganti')
            ->send();

        $this->closeReplaceModal();
    }

    public function unlockScoringEdit(int $scoreId, ScoringFormPresenter $presenter): void
    {
        $record = ExamRegistration::findOrFail($this->recordId);
        $score = ExamScore::query()
            ->where('exam_registration_id', $record->id)
            ->findOrFail($scoreId);

        $examStartAt = Carbon::parse(
            $record->exam_date->format('Y-m-d').' '.trim((string) $record->exam_time)
        );

        if (! $presenter->isDosenScoringTimeLocked($score, $examStartAt)) {
            Notification::make()
                ->warning()
                ->title('Penilaian tidak dikunci')
                ->body('Hanya penilaian yang sudah dikunci yang dapat dibuka untuk diedit.')
                ->send();

            return;
        }

        $score->update(['scoring_edit_unlocked_at' => now()]);

        Notification::make()
            ->success()
            ->title('Edit penilaian dibuka')
            ->body(($score->lecture?->name ?: 'Penguji').' dapat mengubah nilai hingga submit ulang.')
            ->send();
    }

    public function lockScoringEdit(int $scoreId, ScoringFormPresenter $presenter): void
    {
        $record = ExamRegistration::findOrFail($this->recordId);
        $score = ExamScore::query()
            ->where('exam_registration_id', $record->id)
            ->findOrFail($scoreId);

        if (! $presenter->isDosenScoringEditUnlocked($score)) {
            Notification::make()
                ->warning()
                ->title('Penilaian tidak terbuka')
                ->body('Hanya penilaian yang sedang dibuka yang dapat dikunci kembali.')
                ->send();

            return;
        }

        $score->update(['scoring_edit_unlocked_at' => null]);

        Notification::make()
            ->success()
            ->title('Edit penilaian dikunci')
            ->body(($score->lecture?->name ?: 'Penguji').' tidak dapat mengubah nilai lagi.')
            ->send();
    }

    public function openGradeEdit(int $scoreId): void
    {
        $this->closeReplaceModal();
        $this->closeScoreActions();

        $score = ExamScore::query()
            ->where('exam_registration_id', $this->recordId)
            ->findOrFail($scoreId);

        $this->editingScoreId = $scoreId;
        $this->editingGrade = $score->grade !== null ? (string) (int) round($score->grade) : '';
        $this->resetErrorBag('editingGrade');
    }

    public function closeGradeEdit(): void
    {
        $this->editingScoreId = null;
        $this->editingGrade = null;
        $this->resetErrorBag('editingGrade');
        $this->closeScoreActions();
    }

    public function saveGrade(ExamScoreUpdater $updater): void
    {
        $this->validate([
            'editingGrade' => ['required', 'integer', 'min:0', 'max:100'],
        ], [], [
            'editingGrade' => 'nilai',
        ]);

        $score = ExamScore::query()
            ->where('exam_registration_id', $this->recordId)
            ->findOrFail($this->editingScoreId);

        $updater->applyAdminFinalGrade($score, (int) $this->editingGrade);

        Notification::make()
            ->success()
            ->title('Nilai diperbarui')
            ->body('Nilai akhir dan score01–score05 penguji telah disesuaikan.')
            ->send();

        $this->closeGradeEdit();
    }

    public function render()
    {
        $record = ExamRegistration::with([
            'examScores' => fn ($query) => $query->orderBy('examiner_order'),
            'examScores.lecture',
            'examtype',
            'student',
        ])->findOrFail($this->recordId);

        $replacingScore = $this->replacingScoreId
            ? $record->examScores->firstWhere('id', $this->replacingScoreId)
            : null;

        $lecturers = $replacingScore
            ? ExaminerSlotSelectOptions::replacementOptions($record, $replacingScore)
            : collect();

        return view('livewire.exam-scores-detail', compact('record', 'replacingScore', 'lecturers'));
    }
}
