<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Models\User;
use App\Models\ExamType;
use App\Models\ExamScore;
use Illuminate\Http\Request;
use App\Models\GuideExaminer;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use App\DataTables\ExamRegistrationsDataTable;

class ExamRegistrationController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read examregistrations', ['only' => ['index','show']]);
        // $this->middleware('permission:create examregistrations', ['only' => ['create','store']]);
        // $this->middleware('permission:update examregistrations', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete examregistrations', ['only' => ['destroy']]);
    }

    public function index(ExamRegistrationsDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function create()
    {
        $examregistration = new ExamRegistration();
        return view('setting.examination.examregistration-form', array_merge(
            $this->_dataSelection(),
            [
                'examregistration'=> $examregistration,
            ],
        ));
    }

    public function store(Request $request)
    {
        $name = strtoupper($request->name);
        $guideexaminer = GuideExaminer::where('user_id',$request->user_id)->first();
        ExamRegistration::create([
            'exam_type_id'=>$request->exam_type_id,
            'registration_order'=>$request->registration_order,
            'user_id'=>$request->user_id,
            'exam_date'=>$request->exam_date,
            'exam_time'=>$request->exam_time,
            'examiner1_id'=>$guideexaminer->examiner1_id,
            'examiner2_id'=>$guideexaminer->examiner2_id,
            'examiner3_id'=>$guideexaminer->examiner3_id,
            'guide1_id'=>$guideexaminer->guide1_id,
            'guide2_id'=>$guideexaminer->guide2_id,
        ]);
        return to_route('examregistrations.index')->with('success','pendaftaran ujian '.$name.' telah ditambahkan');
    }

    public function edit(ExamRegistration $examregistration)
    {
        return view('setting.examination.examregistration-form', array_merge(
            $this->_dataSelection(),
            [
                'examregistration'=> $examregistration,
            ],
        ));
    }

    public function update(Request $request, ExamRegistration $examregistration)
    {
        $name = strtoupper($examregistration->name);
        $data = $request->all();
        $examregistration->fill($data)->save();

        return to_route('examregistrations.index')->with('success','menu '.$name.' telah diperbarui');
    }

    public function destroy(ExamRegistration $examregistration)
    {
        $name = strtoupper($examregistration->name);
        $examregistration->delete();
        return to_route('examregistrations.index')->with('warning','menu '.$name.' telah dihapus');
    }

    public function scoreSet(ExamRegistration $examregistration)
    {
        ExamScore::create([
            'exam_registration_id'=>$examregistration->id,
            'user_id'=>$examregistration->examiner1_id,
            'examiner_order'=>1,
        ]);
        ExamScore::create([
            'exam_registration_id'=>$examregistration->id,
            'user_id'=>$examregistration->examiner2_id,
            'examiner_order'=>2,
        ]);
        ExamScore::create([
            'exam_registration_id'=>$examregistration->id,
            'user_id'=>$examregistration->examiner3_id,
            'examiner_order'=>3,
        ]);
        ExamScore::create([
            'exam_registration_id'=>$examregistration->id,
            'user_id'=>$examregistration->guide1_id,
            'examiner_order'=>4,
        ]);
        ExamScore::create([
            'exam_registration_id'=>$examregistration->id,
            'user_id'=>$examregistration->guide2_id,
            'examiner_order'=>5,
        ]);
        return redirect()->back()->with('success','data para penguji telah ditambahkan');
    }

    private function _dataSelection()
    {
        return [
            'students' =>  User::role('mahasiswa')->select('name','id')->get()->sort(),
            'lectures' =>  User::role('dosen')->select('name','id')->get()->sort(),
            'exam_types' =>  ExamType::select('name','id')->get(),
        ];
    }
}
