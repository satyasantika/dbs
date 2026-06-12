<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Http\Controllers\Controller;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\User;
use App\Services\Examination\ExamRegistrationExaminerSync;
use Illuminate\Http\Request;

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
        $empty_scores = ExamScore::where([
            'exam_registration_id'=>$examregistration->id,
            'grade'=>NULL,
            ])->exists();
        $exam_scores = ExamScore::where('exam_registration_id',$examregistration->id)->orderBy('examiner_order')->get();
        return view('setting.examination.examscore',compact('exam_scores','examregistration','empty_scores'));
    }

    public function edit(ExamRegistration $examregistration, ExamScore $examscore)
    {
        $lectures =  User::role('dosen')->select('initial','name','id')->get()->sort();
        return view('setting.examination.guideexaminerreplace-form',compact('examscore','examregistration','lectures'));
    }

    public function update(Request $request, ExamRegistration $examregistration, ExamScore $examscore)
    {
        app(ExamRegistrationExaminerSync::class)->replaceExaminer(
            $examregistration,
            $examscore,
            (int) $request->newexaminer_id,
        );

        return to_route('examregistrations.examscores.index', $examregistration)->with('success', 'data penguji telah diperbarui');
    }

    public function markSent(ExamRegistration $examregistration): \Illuminate\Http\RedirectResponse
    {
        $examregistration->update(['sent_at' => now()]);
        return redirect()->back()
            ->with('success', 'Pesan hasil ujian ' . $examregistration->student->name . ' telah ditandai terkirim.');
    }
}
