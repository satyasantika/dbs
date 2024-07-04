<?php

namespace App\Http\Controllers\Examination;

use App\Models\ExamScore;
use Illuminate\Http\Request;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use App\Models\ViewExamRegistration;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        if ( $chief->chief_id != Auth::id() ) {
            return to_route('scoring.index');
        }

        $examinations = ExamScore::where('exam_registration_id',$chief->id)->get();
        return view('examination.chief',compact('examinations','chief'));
    }

    public function pass(ExamRegistration $chief)
    {
        $name = strtoupper($chief->student->name);

        $cek = ExamScore::where([
            'exam_registration_id'=>$chief->id,
            'pass_approved'=>1,
            ])->count();
        if ($cek<5) {
            if (auth()->user()->hasRole('admin')) {
                return to_route('examregistrations.examscores.index',$chief)->with('warning','tidak bisa finalisasi, masih ada nilai yang belum terinput');
            } else {
                return to_route('chief.show',$chief)->with('warning','tidak bisa finalisasi, masih ada nilai yang belum terinput');
            }
        }
        $chief->pass_exam = 1;
        $chief->save();

        if (auth()->user()->hasRole('admin')) {
            return to_route('examregistrations.examscores.index',$chief)->with('success','mahasiswa '.$name.' telah layak dilanjutkan');
        } else {
            return to_route('chief.show',$chief)->with('success','mahasiswa '.$name.' telah layak dilanjutkan');
        }

    }

}
