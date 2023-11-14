<?php

namespace App\Http\Controllers\Examination;

use App\Models\ExamScore;
use Illuminate\Http\Request;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use App\Models\ViewExamRegistration;

class ChiefController extends Controller
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
        $examinations = ViewExamRegistration::where('chief',auth()->id())->get();
        return view('examination.chief',compact('examinations'));
    }

    public function show(ExamRegistration $chief)
    {
        $examinations = ExamScore::where('exam_registration_id',$chief->id)->get();
        // dd($chief);
        return view('examination.chief',compact('examinations','chief'));
    }

    public function pass(ExamRegistration $chief)
    {
        $name = strtoupper($chief->student->name);

        $cek = ExamScore::where([
            'exam_registration_id'=>$chief->exam_registration_id,
            'pass_approved'=>1,
            ])->count();
        if ($cek<5) {
            return to_route('chief.show',$chief)->with('warning','tidak bisa finalisasi, masih ada nilai yang belum terinput');
        }
        $chief->pass_exam = 1;
        $chief->save();

        return to_route('chief.show',$chief)->with('success','mahasiswa '.$name.' telah layak dilanjutkan');
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
