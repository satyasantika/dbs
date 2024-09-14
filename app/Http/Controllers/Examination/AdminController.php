<?php

namespace App\Http\Controllers\Examination;

use Illuminate\Http\Request;
use App\Models\ViewExamScore;
use App\Http\Controllers\Controller;
use App\Models\ViewExamRegistration;

class AdminController extends Controller
{
    public function getExaminerScoringYet()
    {
        $exam_scores = ViewExamScore::whereNull('pass_approved')->get();
        return view('examination.admin.scoringyet',compact('exam_scores'));
    }

    public function getSetScoringToExaminerYet()
    {
        $exam_registration_id_on_exam_scores = ViewExamScore::select('exam_registration_id')->groupBy('exam_registration_id')->get()->pluck('exam_registration_id');
        $exam_registrations = ViewExamRegistration::whereNotIn('id',$exam_registration_id_on_exam_scores)->get();
        return view('examination.admin.setscoringtoexamineryet',compact('exam_registrations'));
    }
}
