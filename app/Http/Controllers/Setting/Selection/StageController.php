<?php

namespace App\Http\Controllers\Setting\Selection;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\SelectionStage;
use App\Http\Controllers\Controller;
use App\DataTables\SelectionStagesDataTable;

class StageController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read selection stages', ['only' => ['index','show']]);
        $this->middleware('permission:create selection stages', ['only' => ['create','store']]);
        $this->middleware('permission:update selection stages', ['only' => ['edit','update']]);
        $this->middleware('permission:delete selection stages', ['only' => ['destroy']]);
    }

    public function index(SelectionStagesDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function create()
    {
        $selectionstage = new SelectionStage();
        return view('setting.selection.stage-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionstage'=> $selectionstage,
            ],
        ));
    }

    public function store(Request $request)
    {
        SelectionStage::create($request->all());
        return to_route('selectionstages.index')->with('success','data telah ditambahkan');
    }

    public function edit(SelectionStage $selectionstage)
    {
        return view('setting.selection.stage-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionstage'=> $selectionstage,
            ],
        ));
    }

    public function update(Request $request, SelectionStage $selectionstage)
    {
        $data = $request->all();
        $selectionstage->fill($data)->save();

        return to_route('selectionstages.index')->with('success','data telah diperbarui');
    }

    public function destroy(SelectionStage $selectionstage)
    {
        $selectionstage->delete();
        return to_route('selectionstages.index')->with('warning','data telah dihapus');
    }

    private function _dataSelection()
    {
        return [
            'lectures' =>  User::role('dosen')->select('name','id')->get()->sort(),
            'students' =>  User::role('mahasiswa')->select('name','id')->get()->sort(),
        ];
    }
}
