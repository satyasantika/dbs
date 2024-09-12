<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Models\User;
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
        $this->middleware('permission:read exam registrations', ['only' => ['index']]);
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

    public function edit(ExamRegistration $examregistration, ExamScore $examscore)
    {
        $lectures =  User::role('dosen')->select('initial','name','id')->get()->sort();
        return view('setting.examination.guideexaminerreplace-form',compact('examscore','examregistration','lectures'));
    }

    public function update(Request $request, ExamRegistration $examregistration, ExamScore $examscore)
    {
        $examscore->user_id = $request->newexaminer_id;
        $examscore->save();

        if ($examscore->examiner_order==1) {
            $examregistration->examiner1_id = $request->newexaminer_id;
        }
        if ($examscore->examiner_order==2) {
            $examregistration->examiner2_id = $request->newexaminer_id;
        }
        if ($examscore->examiner_order==3) {
            $examregistration->examiner3_id = $request->newexaminer_id;
        }
        if ($examscore->examiner_order==4) {
            $examregistration->guide1_id = $request->newexaminer_id;
        }
        if ($examscore->examiner_order==5) {
            $examregistration->guide2_id = $request->newexaminer_id;
        }
        if ($request->oldexaminer_id==$examregistration->chief_id) {
            $examregistration->chief_id = $request->newexaminer_id;
        }

        $examregistration->save();

        return to_route('examregistrations.examscores.index',$examregistration)->with('success','data penguji telah diperbarui');
    }
}
