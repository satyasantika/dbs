<?php

namespace App\Http\Controllers\Examination;

use App\Models\ExamScore;
use App\Http\Controllers\Controller;
use App\Models\ExamRegistration;

class AdminController extends Controller
{
    public function getExaminerScoringYet()
    {
        $exam_scores = ExamScore::whereNull('pass_approved')->get();
        $exam_registrations = ExamRegistration::with(['student','examtype','examiner1','examiner2','examiner3','guide1','guide2','chief'])
            ->whereIn('id',$exam_scores->pluck('exam_registration_id'))
            ->orderBy('exam_date')
            ->orderBy('exam_time')
            ->get()
            ->keyBy('id');
        return view('examination.admin.scoringyet',compact('exam_registrations'));
    }

    public function getSetScoringToExaminerYet()
    {
        $exam_registration_id_on_exam_scores = ExamScore::select('exam_registration_id')->groupBy('exam_registration_id')->get()->pluck('exam_registration_id');
        $exam_registrations = ExamRegistration::with(['student','examtype','examiner1','examiner2','examiner3','guide1','guide2','chief'])
            ->whereNotIn('id',$exam_registration_id_on_exam_scores)
            ->orderBy('exam_date')
            ->orderBy('exam_time')
            ->get();
        return view('examination.admin.setscoringtoexamineryet',compact('exam_registrations'));
    }
}
