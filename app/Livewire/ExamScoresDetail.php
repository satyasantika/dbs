<?php

namespace App\Livewire;

use App\Models\ExamRegistration;
use Livewire\Component;

class ExamScoresDetail extends Component
{
    public int $recordId;

    public function render()
    {
        $record = ExamRegistration::with([
            'examScores' => fn ($query) => $query->orderBy('examiner_order'),
            'examScores.lecture',
            'examtype',
            'student',
        ])->findOrFail($this->recordId);

        return view('livewire.exam-scores-detail', compact('record'));
    }
}
