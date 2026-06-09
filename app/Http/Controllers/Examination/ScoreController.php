<?php

namespace App\Http\Controllers\Examination;

use App\Filament\Dosen\Pages\EditScoring;
use App\Filament\Dosen\Pages\Scoring;
use App\Models\ExamScore;
use App\Models\ExamFormItem;
use Illuminate\Http\Request;
use App\Models\ExamRegistration;
use App\DataTables\ScoringDataTable;
use App\Filament\Resources\ExamRegistrationResource;
use App\Http\Controllers\Controller;
use App\Services\Examination\ExamScoreUpdater;
use App\Services\Examination\ScoringFormPresenter;
use Illuminate\Support\Facades\Auth;

class ScoreController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read scoring', ['only' => ['index','show']]);
        // $this->middleware('permission:create scoring', ['only' => ['create','store']]);
        // $this->middleware('permission:update scoring', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete scoring', ['only' => ['destroy']]);
    }

    public function index(ScoringDataTable $dataTable)
    {
        if (auth()->user()->hasRole('dosen')) {
            return redirect(Scoring::getUrl(['activeTab' => 'unscored']));
        }

        $title = 'Penilaian Ujian';
        return $dataTable->render('layouts.setting',compact('title'));
    }

    public function archieves()
    {
        $exam_dates = ExamRegistration::join('exam_scores AS es', 'exam_registrations.id', '=', 'es.exam_registration_id')
            ->where('es.user_id', auth()->id())
            ->whereNotNull('es.grade')
            ->orderBy('exam_registrations.exam_date', 'desc')
            ->distinct()
            ->pluck('exam_registrations.exam_date');
        return view('examination.scoring-archieves',compact('exam_dates'));
    }

    public function edit(ExamScore $scoring)
    {
        if ($scoring->user_id != Auth::id() && ! auth()->user()->can('force edit score')) {
            return to_route('scoring.index');
        }

        if (auth()->user()->hasRole('dosen')) {
            return redirect(EditScoring::getUrl(['record' => $scoring->id]));
        }

        $examregistration = ExamRegistration::find($scoring->exam_registration_id);
        $scoring->loadMissing(['registration.student', 'registration.examtype', 'lecture']);
        $form_items = ExamFormItem::select('id', 'name', 'exam_type_id')
            ->where('exam_type_id', $examregistration->exam_type_id)
            ->get();

        $formData = app(ScoringFormPresenter::class)->present($scoring, $examregistration, $form_items);

        return view('examination.scoring-form', [
            'formData' => $formData,
            'scoring' => $scoring,
            'returnUrl' => $this->scoringReturnUrl(),
        ]);
    }

    public function update(Request $request, ExamScore $scoring, ExamScoreUpdater $updater)
    {
        if ($scoring->user_id != Auth::id() && ! auth()->user()->can('force edit score')) {
            return to_route('scoring.index');
        }

        $studentName = strtoupper($scoring->registration?->student?->name ?? 'MAHASISWA');
        $updater->update($scoring, $request->all());

        return redirect($this->scoringReturnUrl())
            ->with('success', 'data penilaian '.$studentName.' telah diperbarui');
    }

    private function scoringReturnUrl(): string
    {
        if (auth()->user()->hasRole('admin')) {
            return ExamRegistrationResource::getUrl();
        }

        if (auth()->user()->hasRole('dosen')) {
            return Scoring::getUrl(['activeTab' => 'unscored']);
        }

        return route('scoring.index');
    }
}
