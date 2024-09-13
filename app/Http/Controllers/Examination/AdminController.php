<?php

namespace App\Http\Controllers\Examination;

use Illuminate\Http\Request;
use App\Models\ViewExamScore;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function getExaminerScoringYet()
    {
        $exam_scores = ViewExamScore::whereNull('pass_approved')->get();
        return view('examination.admin.scoringyet',compact('exam_scores'));
    }
}
