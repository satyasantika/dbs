<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Models\User;
use App\Models\ExamType;
use App\Models\ExamScore;
use Illuminate\Http\Request;
use App\Models\GuideExaminer;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use App\Models\ViewExamRegistration;
use App\DataTables\ExamRegistrationsDataTable;

class ExamRegistrationController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read exam registrations', ['only' => ['index','index2']]);
        // $this->middleware('permission:create examregistrations', ['only' => ['create','store']]);
        $this->middleware('permission:update exam registrations', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete examregistrations', ['only' => ['destroy']]);
    }

    public function index(ExamRegistrationsDataTable $dataTable)
    {
        $title = 'Jadwal Ujian';
        return $dataTable->render('layouts.setting',compact('title'));
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
            'room'=>$request->room,
            'title'=>$request->title,
            'ipk'=>$request->ipk,
            'examiner1_id'=>$guideexaminer->examiner1_id,
            'examiner2_id'=>$guideexaminer->examiner2_id,
            'examiner3_id'=>$guideexaminer->examiner3_id,
            'guide1_id'=>$guideexaminer->guide1_id,
            'guide2_id'=>$guideexaminer->guide2_id,
            'chief_id'=>$guideexaminer->chief_id,
        ]);
        User::find($request->user_id)->givePermissionTo('join exam');
        return $this->showByStudent($request->user_id)->with('success','pendaftaran ujian '.$name.' telah ditambahkan');
    }

    public function edit(ExamRegistration $examregistration)
    {
        $chiefs = User::whereIn('id',[
                        $examregistration->examiner1_id,
                        $examregistration->examiner2_id,
                        $examregistration->examiner3_id,
                        ])->role('dosen')->select('initial','name','id')->get()->sort();
        $exam_score_set = ExamScore::where('exam_registration_id',$examregistration->id)->exists();
        return view('setting.examination.examregistration-form', array_merge(
            $this->_dataSelection(),
            [
                'examregistration'=> $examregistration,
                'chiefs'=> $chiefs,
                'exam_score_set'=> $exam_score_set,
            ],
        ));
    }

    public function update(Request $request, ExamRegistration $examregistration)
    {
        $name = strtoupper($examregistration->name);
        $data = $request->all();
        $examregistration->fill($data)->save();

        if ($examregistration->exam_type_id == 1) {
            $tanggal_ujian = 'proposal_date';
        }
        if ($examregistration->exam_type_id == 2) {
            $tanggal_ujian = 'seminar_date';
        }
        if ($examregistration->exam_type_id == 3) {
            $tanggal_ujian = 'thesis_date';
        }

        GuideExaminer::where('user_id',$examregistration->user_id)->update([
            'examiner1_id'=>$examregistration->examiner1_id,
            'examiner2_id'=>$examregistration->examiner2_id,
            'examiner3_id'=>$examregistration->examiner3_id,
            'guide1_id'=>$examregistration->guide1_id,
            'guide2_id'=>$examregistration->guide2_id,
            'chief_id'=>$examregistration->chief_id,
            $tanggal_ujian=>$examregistration->exam_date,
        ]);

        return $this->showByStudent($examregistration->user_id)->with('success','data pendaftaran '.$name.' telah diperbarui');
    }

    public function destroy(ExamRegistration $examregistration)
    {
        $name = strtoupper($examregistration->name);
        $examregistration->delete();
        return $this->showByStudent($examregistration->user_id)->with('warning','pendaftaran '.$name.' telah dihapus');
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

    public function createByStudent($student_id)
    {
        $chiefs = User::role('dosen')->select('initial','name','id')->get()->sort();
        $examregistration = new ExamRegistration();
        return view('setting.examination.examregistration-form', array_merge(
            [
                'student' => User::find($student_id),
                'examregistration' => $examregistration,
                'exam_score_set' => false,
                'chiefs'=>$chiefs,
            ],
            $this->_dataSelection(),
        ));
    }

    public function showByStudent($student_id)
    {
        $student = User::find($student_id);
        $examregistrations = ViewExamRegistration::where('user_id',$student_id)->orderBy('mahasiswa')->get();
        return view('examination.examregistration',compact('examregistrations','student'));
    }

    private function _dataSelection()
    {
        $pass_students = GuideExaminer::whereNull('thesis_date')->pluck('user_id');
        return [
            'students' =>  User::role('mahasiswa')->select('name','id','username')->whereIn('id',$pass_students)->get()->sort(),
            'lectures' =>  User::role('dosen')->select('initial','name','id')->get()->sort(),
            'exam_types' =>  ExamType::select('name','id')->get(),
        ];
    }

    public function index2(ExamRegistrationsDataTable $dataTable, $id="")
    {
        return $dataTable->with('user_id', $id)->render('layouts.setting');
    }

}
