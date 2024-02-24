<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Models\ExamScore;
use App\Models\ExamFormItem;
use Illuminate\Http\Request;
use App\Models\ViewExamScore;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;

class ExamScoreController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read scoring', ['only' => ['index','show']]);
        // $this->middleware('permission:create scoring', ['only' => ['create','store']]);
        // $this->middleware('permission:update scoring', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete scoring', ['only' => ['destroy']]);
    }

    public function index(ExamRegistration $examregistration)
    {
        $empty_scores = ViewExamScore::where([
            'exam_registration_id'=>$examregistration->id,
            'grade'=>NULL,
            ])->exists();
        $exam_scores = ViewExamScore::where('exam_registration_id',$examregistration->id)->orderBy('examiner_order')->get();
        return view('setting.examination.examscore',compact('exam_scores','examregistration','empty_scores'));
    }
}
