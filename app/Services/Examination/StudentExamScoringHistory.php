<?php

namespace App\Services\Examination;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use Illuminate\Support\Collection;

class StudentExamScoringHistory
{
    /**
     * @return Collection<int, array{
     *     exam_date: string,
     *     exam_time: string,
     *     exam_type_name: string,
     *     title: string,
     *     grade_display: string,
     *     revision_note: string,
     *     final_decision: string,
     * }>
     */
    public static function forExaminer(ExamScore $currentScore, int $examinerUserId): Collection
    {
        $registration = $currentScore->registration;

        if (! $registration) {
            return collect();
        }

        return ExamRegistration::query()
            ->with([
                'examtype:id,name',
                'examScores' => fn ($query) => $query
                    ->where('user_id', $examinerUserId)
                    ->select('id', 'exam_registration_id', 'user_id', 'grade', 'letter', 'revision', 'revision_note', 'pass_approved'),
            ])
            ->where('user_id', $registration->user_id)
            ->where('id', '!=', $registration->id)
            ->whereHas('examScores', fn ($query) => $query->where('user_id', $examinerUserId))
            ->orderByDesc('exam_date')
            ->orderByDesc('exam_time')
            ->get()
            ->map(function (ExamRegistration $pastRegistration): array {
                /** @var ExamScore|null $score */
                $score = $pastRegistration->examScores->first();

                return [
                    'exam_date' => $pastRegistration->exam_date?->format('d M Y') ?? '—',
                    'exam_time' => filled($pastRegistration->exam_time)
                        ? \Illuminate\Support\Carbon::parse($pastRegistration->exam_time)->format('H:i')
                        : '—',
                    'exam_type_name' => $pastRegistration->examtype?->name ?? '—',
                    'title' => filled($pastRegistration->title) ? $pastRegistration->title : '—',
                    'grade_display' => self::gradeDisplay($score),
                    'revision_note' => self::revisionNoteDisplay($score),
                    'final_decision' => self::finalDecisionLabel($pastRegistration, $score),
                ];
            })
            ->values();
    }

    protected static function gradeDisplay(?ExamScore $score): string
    {
        if (! $score || ! filled($score->grade)) {
            return 'Belum dinilai';
        }

        if (filled($score->letter)) {
            return $score->letter.' ('.number_format((float) $score->grade, 2).')';
        }

        return number_format((float) $score->grade, 2);
    }

    protected static function revisionNoteDisplay(?ExamScore $score): string
    {
        if (! $score) {
            return '—';
        }

        return match ((int) $score->revision) {
            0 => 'Tidak perlu revisi',
            1 => filled($score->revision_note) ? $score->revision_note : 'Revisi minor — belum diisi',
            2 => filled($score->revision_note) ? $score->revision_note : 'Revisi mayor — belum diisi',
            default => '—',
        };
    }

    protected static function finalDecisionLabel(ExamRegistration $registration, ?ExamScore $score): string
    {
        if (! $score || ! filled($score->grade)) {
            return 'Belum dinilai';
        }

        $pass = (bool) $score->pass_approved;

        return match ((int) $registration->exam_type_id) {
            1 => $pass
                ? 'Rencana penelitian layak dilanjutkan untuk diteliti'
                : 'Rencana penelitian tidak layak dilanjutkan untuk diteliti',
            2 => $pass
                ? 'Seminar Hasil Penelitian layak disidangkan'
                : 'Seminar Hasil Penelitian tidak layak disidangkan',
            default => $pass
                ? 'Mahasiswa dinyatakan LULUS'
                : 'Mahasiswa dinyatakan TIDAK LULUS',
        };
    }
}
