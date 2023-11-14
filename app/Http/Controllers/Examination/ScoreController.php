<?php

namespace App\Http\Controllers\Examination;

use App\Models\ExamScore;
use App\Models\ExamFormItem;
use Illuminate\Http\Request;
use App\Models\ViewExamScore;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;

class ScoreController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read scoring', ['only' => ['index','show']]);
        // $this->middleware('permission:create scoring', ['only' => ['create','store']]);
        // $this->middleware('permission:update scoring', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete scoring', ['only' => ['destroy']]);
    }

    public function index()
    {
        $exam_scores = ViewExamScore::where('user_id',auth()->id())->get();
        return view('examination.scoring',compact('exam_scores'));
    }

    public function edit(ExamScore $scoring)
    {
        $examregistration = ExamRegistration::find($scoring->exam_registration_id);
        $form_items = ExamFormItem::select('id','name')->where('exam_type_id',$examregistration->exam_type_id)->get();
        return view('examination.scoring-form',compact('form_items','scoring'));
    }

    public function update(Request $request, ExamScore $scoring)
    {
        $name = strtoupper($scoring->name);
        $data = $request->all();
        $grade = 0;
        for ($i=0; $i < 5; $i++) {
            $score = 'score0'.$i+1;
            $grade += $request->$score;
        }
        $final_grade = round($grade/5,2);
        $data['exam_registration_id'] = $scoring->exam_registration_id;
        $data['grade'] = $final_grade;
        $data['letter'] = $this->_convertToLetter($final_grade);
        $scoring->fill($data)->save();

        $grade_sum = ExamScore::where('exam_registration_id',$scoring->exam_registration_id)->sum('grade');
        $final_grade = round($grade_sum/5,2);
        $examregistration = ExamRegistration::find($scoring->exam_registration_id);
        $examregistration->grade = $final_grade;
        $examregistration->letter = $this->_convertToLetter($final_grade);
        $examregistration->save();

        return to_route('scoring.index')->with('success','data '.$name.' telah diperbarui');
    }

    private function _convertToLetter($grade)
    {
        if ($grade >= 85)
        { return 'A'; }
        elseif ($grade >= 77)
        { return 'A-'; }
        elseif ($grade >= 69)
        { return 'B+'; }
        elseif ($grade >= 61)
        { return 'B'; }
        elseif ($grade >= 53)
        { return 'B-'; }
        elseif ($grade >= 45)
        { return 'C+'; }
        elseif ($grade >= 37)
        { return 'C'; }
        elseif ($grade >= 29)
        { return 'C-'; }
        elseif ($grade >= 21)
        { return 'D'; }
        else
        { return 'E'; }
    }
}
