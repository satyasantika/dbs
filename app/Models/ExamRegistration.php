<?php

namespace App\Models;

use App\Models\User;
use App\Models\ExamType;
use App\Models\ExamScore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamRegistration extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'exam_date'         => 'date',
        'pass_exam'         => 'boolean',
        'sent_at'           => 'datetime',
        'invited_at'        => 'datetime',
        'corrected_at'      => 'datetime',
        'invited_exam_date' => 'date',
    ];

    public function syncInvitedScheduleSnapshot(): void
    {
        $this->forceFill([
            'invited_exam_date' => $this->exam_date,
            'invited_exam_time' => $this->exam_time,
            'invited_room'      => $this->room,
        ])->save();
    }

    public function hasScheduleChangedSinceInvite(): bool
    {
        if (!$this->invited_at) {
            return false;
        }

        return $this->normalizedScheduleDate($this->exam_date) !== $this->normalizedScheduleDate($this->invited_exam_date)
            || $this->normalizedScheduleTime($this->exam_time) !== $this->normalizedScheduleTime($this->invited_exam_time)
            || $this->normalizedScheduleRoom($this->room) !== $this->normalizedScheduleRoom($this->invited_room);
    }

    protected function normalizedScheduleDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
    }

    protected function normalizedScheduleTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($value)->format('H:i');
    }

    protected function normalizedScheduleRoom(mixed $value): ?string
    {
        $room = trim((string) ($value ?? ''));

        return $room !== '' ? $room : null;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('exam_registrations.' . ($field ?? $this->getRouteKeyName()), $value)
                    ->firstOrFail();
    }

    public function examtype()
    {
        return $this->belongsTo(ExamType::class,'exam_type_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function examiner1()
    {
        return $this->belongsTo(User::class,'examiner1_id');
    }

    public function examiner2()
    {
        return $this->belongsTo(User::class,'examiner2_id');
    }

    public function examiner3()
    {
        return $this->belongsTo(User::class,'examiner3_id');
    }

    public function guide1()
    {
        return $this->belongsTo(User::class,'guide1_id');
    }

    public function guide2()
    {
        return $this->belongsTo(User::class,'guide2_id');
    }

    public function chief()
    {
        return $this->belongsTo(User::class,'chief_id');
    }

    public function examScores(): HasMany
    {
        return $this->hasMany(ExamScore::class, 'exam_registration_id')->orderBy('examiner_order');
    }

    public function scopeWhereExaminerScoringIncomplete(Builder $query): Builder
    {
        return $query->whereHas('examScores', fn (Builder $query) => $query->whereNull('grade'));
    }

    public function scopeWhereExaminerScoringComplete(Builder $query): Builder
    {
        return $query->whereHas('examScores')
            ->whereDoesntHave('examScores', fn (Builder $query) => $query->whereNull('grade'));
    }

    public function hasIncompleteExaminerScoring(): bool
    {
        return $this->examScores()->whereNull('grade')->exists();
    }

    /**
     * Semua slot penguji/pembimbing yang diisi sudah memiliki baris exam_scores dengan grade terisi,
     * dan hasil belum pernah dikirim ke mahasiswa (sent_at null).
     */
    public function scopeReadyToNotifyStudent(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->whereRaw('(
                (CASE WHEN examiner1_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN examiner2_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN examiner3_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN guide1_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN guide2_id IS NOT NULL THEN 1 ELSE 0 END)
            ) > 0')
            ->whereRaw('(
                SELECT COUNT(*) FROM exam_scores
                WHERE exam_scores.exam_registration_id = exam_registrations.id
                AND exam_scores.grade IS NOT NULL
                AND (
                    (exam_scores.user_id = exam_registrations.examiner1_id AND exam_registrations.examiner1_id IS NOT NULL)
                    OR (exam_scores.user_id = exam_registrations.examiner2_id AND exam_registrations.examiner2_id IS NOT NULL)
                    OR (exam_scores.user_id = exam_registrations.examiner3_id AND exam_registrations.examiner3_id IS NOT NULL)
                    OR (exam_scores.user_id = exam_registrations.guide1_id AND exam_registrations.guide1_id IS NOT NULL)
                    OR (exam_scores.user_id = exam_registrations.guide2_id AND exam_registrations.guide2_id IS NOT NULL)
                )
            ) = (
                (CASE WHEN examiner1_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN examiner2_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN examiner3_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN guide1_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN guide2_id IS NOT NULL THEN 1 ELSE 0 END)
            )');
    }
}

