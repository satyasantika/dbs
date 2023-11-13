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
        return $dataTable->render('layouts.setting');
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
        if ($request->parent_id > 0) {
            guideexaminer::find($request->parent_id)->children()->create($request->all());
        } else {
            $request->parent_id = null;
            guideexaminer::create($request->all());
        }
        return to_route('guideexaminers.index')->with('success','menu '.$name.' telah ditambahkan');
    }

    public function edit(GuideExaminer $guideexaminer)
    {
        return view('setting.examination.guideexaminer-form', array_merge(
            $this->_dataSelection(),
            [
                'guideexaminer'=> $guideexaminer,
            ],
        ));
    }

    public function update(Request $request, GuideExaminer $guideexaminer)
    {
        $name = strtoupper($guideexaminer->name);
        $data = $request->all();
        $guideexaminer->fill($data)->save();

        return to_route('guideexaminers.index')->with('success','menu '.$name.' telah diperbarui');
    }

    public function destroy(GuideExaminer $guideexaminer)
    {
        $name = strtoupper($guideexaminer->name);
        $guideexaminer->delete();
        return to_route('guideexaminers.index')->with('warning','menu '.$name.' telah dihapus');
    }

    private function _dataSelection()
    {
        return [
            'students' =>  User::role('mahasiswa')->select('name','id')->get()->sort(),
            'lectures' =>  User::role('dosen')->select('name','id')->get()->sort(),
        ];
    }
}
