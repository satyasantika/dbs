<?php

namespace App\Http\Controllers\Setting\Selection;

use Illuminate\Http\Request;
use App\Models\SelectionStage;
use App\Models\SelectionElement;
use App\Http\Controllers\Controller;
use App\DataTables\SelectionElementsDataTable;

class ElementController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read selection elements', ['only' => ['index','show']]);
        $this->middleware('permission:create selection elements', ['only' => ['create','store']]);
        $this->middleware('permission:update selection elements', ['only' => ['edit','update']]);
        $this->middleware('permission:delete selection elements', ['only' => ['destroy']]);
    }

    public function index(SelectionElementsDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function create()
    {
        $selectionelement = new SelectionElement();
        return view('setting.selection.element-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionelement'=> $selectionelement,
            ],
        ));
    }

    public function store(Request $request)
    {
        Selectionelement::create($request->all());
        return to_route('selectionelements.index')->with('success','data telah ditambahkan');
    }

    public function edit(Selectionelement $selectionelement)
    {
        return view('setting.selection.element-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionelement'=> $selectionelement,
            ],
        ));
    }

    public function update(Request $request, SelectionElement $selectionelement)
    {
        $data = $request->all();
        $selectionelement->fill($data)->save();

        return to_route('selectionelements.index')->with('success','data telah diperbarui');
    }

    public function destroy(SelectionElement $selectionelement)
    {
        $selectionelement->delete();
        return to_route('selectionelements.index')->with('warning','data telah dihapus');
    }

    private function _dataSelection()
    {
        return [
            'stages' =>  SelectionStage::where('final',0)->latest()->get(),
            'elements' =>  ['title','urgency','novelty','impact','references'],
        ];
    }
}
