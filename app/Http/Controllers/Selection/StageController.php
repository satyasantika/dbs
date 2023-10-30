<?php

namespace App\Http\Controllers\Selection;

use Illuminate\Http\Request;
use App\Models\SelectionStage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class StageController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read selection stages', ['only' => ['index','show']]);
        $this->middleware('permission:create selection stages', ['only' => ['create','store']]);
        $this->middleware('permission:update stages', ['only' => ['edit','update']]);
        $this->middleware('permission:delete stages', ['only' => ['destroy']]);
    }

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = Auth::id();
        $input['stage_order'] = 2; //make to model
        SelectionStage::create($input);
        return redirect()->back()->with('success','usulanmu telah ditambahkan');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, SelectionStage $selectionstage)
    {
        $name = strtoupper(User::find($selectionstage->user_id)->name);
        $data = $request->all();
        $selectionstage->fill($data)->save();

        return to_route('stages.index')->with('success','pembimbing/penguji '.$name.' telah disahkan');
    }

    public function destroy(SelectionStage $selectionstage)
    {
        $name = strtoupper(User::find($selectionstage->user_id)->name);
        $selectionstage->delete();
        return to_route('stages.index')->with('warning','usulan '.$name.' telah dihapus');
    }
}
