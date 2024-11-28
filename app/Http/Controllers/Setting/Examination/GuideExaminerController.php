<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\GuideExaminer;
use App\Http\Controllers\Controller;
use App\DataTables\GuideExaminersDataTable;

class GuideExaminerController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read guideexaminers', ['only' => ['index','show']]);
        // $this->middleware('permission:create guideexaminers', ['only' => ['create','store']]);
        // $this->middleware('permission:update guideexaminers', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete guideexaminers', ['only' => ['destroy']]);
    }

    public function index(GuideExaminersDataTable $dataTable)
    {
        $title = 'Data Ujian';
        return $dataTable->render('layouts.setting',compact('title'));
    }

    public function create()
    {
        $guideexaminer = new GuideExaminer();
        return view('setting.examination.guideexaminer-form', array_merge(
            $this->_dataSelection(),
            [
                'guideexaminer'=> $guideexaminer,
            ],
        ));
    }

    public function store(Request $request)
    {
        $name = strtoupper($request->name);
        GuideExaminer::create($request->all());
        return to_route('guideexaminers.index')->with('success','Penguji '.$name.' telah ditambahkan');
    }

    public function edit(GuideExaminer $guideexaminer)
    {
        $chiefs = User::whereIn('id',[
                        $guideexaminer->examiner1_id,
                        $guideexaminer->examiner2_id,
                        $guideexaminer->examiner3_id,
                        ])->role('dosen')->select('initial','name','id')->get()->sort();
        return view('setting.examination.guideexaminer-form', array_merge(
            $this->_dataSelection(),
            [
                'guideexaminer'=> $guideexaminer,
                'chiefs'=> $chiefs,
            ],
        ));
    }

    public function update(Request $request, GuideExaminer $guideexaminer)
    {
        $name = strtoupper($guideexaminer->name);
        $data = $request->all();
        $guideexaminer->fill($data)->save();

        return to_route('guideexaminers.index')->with('success','Penguji '.$name.' telah diperbarui');
    }

    public function destroy(GuideExaminer $guideexaminer)
    {
        $name = strtoupper($guideexaminer->name);
        $guideexaminer->delete();
        return to_route('guideexaminers.index')->with('warning','Penguji '.$name.' telah dihapus');
    }

    private function _dataSelection()
    {
        $available_students = GuideExaminer::pluck('user_id');
        return [
            'students' =>  User::role(['mahasiswa'])->select('name','id')->whereNotIn('id',$available_students)->get()->sort(),
            'lectures' =>  User::role('dosen')->select('initial','name','id')->get()->sort(),
        ];
    }
}
