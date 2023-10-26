<?php

namespace App\Http\Controllers\Setting\Selection;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\SelectionElement;
use App\Http\Controllers\Controller;
use App\Models\SelectionElementComment;
use App\DataTables\SelectionElementCommentsDataTable;

class ElementCommentController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read selection element comments', ['only' => ['index','show']]);
        $this->middleware('permission:create selection element comments', ['only' => ['create','store']]);
        $this->middleware('permission:update selection element comments', ['only' => ['edit','update']]);
        $this->middleware('permission:delete selection element comments', ['only' => ['destroy']]);
    }

    public function index(SelectionElementCommentsDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function create()
    {
        $selectionelementcomment = new SelectionElementComment();
        return view('setting.selection.elementcomment-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionelementcomment'=> $selectionelementcomment,
            ],
        ));
    }

    public function store(Request $request)
    {
        SelectionElementComment::create($request->all());
        return to_route('selectionelementcomments.index')->with('success','data telah ditambahkan');
    }

    public function edit(SelectionElementComment $selectionelementcomment)
    {
        return view('setting.selection.elementcomment-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionelementcomment'=> $selectionelementcomment,
            ],
        ));
    }

    public function update(Request $request, SelectionElementComment $selectionelementcomment)
    {
        $data = $request->all();
        $selectionelementcomment->fill($data)->save();

        return to_route('selectionelementcomments.index')->with('success','data telah diperbarui');
    }

    public function destroy(SelectionElementComment $selectionelementcomment)
    {
        $selectionelementcomment->delete();
        return to_route('selectionelementcomments.index')->with('warning','data telah dihapus');
    }

    private function _dataSelection()
    {
        return [
            'elements' =>  SelectionElement::with('stage')->where('approved',0)->latest()->get(),
            'verificators' =>  User::role('dosen')->get(),
        ];
    }
}
