<?php

namespace App\Http\Controllers\Examination;

use App\Models\User;
use App\Models\ExamScore;
use Illuminate\Http\Request;
use App\Models\ViewExamScore;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use App\Models\ViewExamRegistration;

class StudentController extends Controller
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
        $examinations = ViewExamRegistration::where('user_id',auth()->id())->get();
        return view('examination.student.index',compact('examinations'));
    }

    public function getRevision(ExamRegistration $student)
    {
        // dd($student);
        $exam_scores = ViewExamScore::where('exam_registration_id',$student->id)->get();
        return view('examination.student.get-revision',compact('student','exam_scores'));
    }

    public function show(ExamRegistration $chief)
    {
        $examinations = ExamScore::where('exam_registration_id',$chief->id)->get();
        // dd($chief);
        return view('examination.chief',compact('examinations','chief'));
    }

    function setRevisionDate()
    {
        return view('examination.student.set-date');
    }

    public function showRevisionByDate(Request $request)
    {
        $user_id = User::where('username',$request->student_id)->first()->id;
        $examination = ViewExamRegistration::where('user_id',$user_id)->where('exam_date',$request->exam_date)->first();
        $belum_menilai = ViewExamScore::where('exam_registration_id',$examination->id)->whereNull('pass_approved')->doesntExist();
        return view('examination.student.result',compact('examination','belum_menilai'));
    }

}
