<?php

namespace App\Http\Controllers\Setting\Selection;

use App\Models\GuideGroup;
use Illuminate\Http\Request;
use App\Models\SelectionGuide;
use App\Models\SelectionStage;
use App\Http\Controllers\Controller;
use App\DataTables\SelectionGuidesDataTable;

class GuideController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read selection guides', ['only' => ['index','show']]);
        $this->middleware('permission:create selection guides', ['only' => ['create','store']]);
        $this->middleware('permission:update selection guides', ['only' => ['edit','update']]);
        $this->middleware('permission:delete selection guides', ['only' => ['destroy']]);
    }

    public function index(SelectionGuidesDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }


    public function create()
    {
        $selectionguide = new SelectionGuide();
        return view('setting.selection.guide-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionguide'=> $selectionguide,
            ],
        ));
    }

    public function store(Request $request)
    {
        SelectionGuide::create($request->all());
        return to_route('selectionguides.index')->with('success','data telah ditambahkan');
    }

    public function edit(SelectionGuide $selectionguide)
    {
        return view('setting.selection.guide-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionguide'=> $selectionguide,
            ],
        ));
    }

    public function update(Request $request, SelectionGuide $selectionguide)
    {
        $data = $request->all();
        $selectionguide->fill($data)->save();

        return to_route('selectionguides.index')->with('success','data telah diperbarui');
    }

    public function destroy(SelectionGuide $selectionguide)
    {
        $selectionguide->delete();
        return to_route('selectionguides.index')->with('warning','data telah dihapus');
    }

    private function _dataSelection()
    {
        return [
            'stages' =>  SelectionStage::where('final',0)->latest()->get(),
            'groups' =>  GuideGroup::where('active',1)->orderBy('id')->get(),
        ];
    }
}
