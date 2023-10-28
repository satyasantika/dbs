<?php

namespace App\Http\Controllers\Setting\Selection;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\GuideAllocation;
use App\Http\Controllers\Controller;
use App\DataTables\GuideAllocationsDataTable;

class GuideAllocationController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read guide allocations', ['only' => ['index','show']]);
        $this->middleware('permission:create guide allocations', ['only' => ['create','store']]);
        $this->middleware('permission:update guide allocations', ['only' => ['edit','update']]);
        $this->middleware('permission:delete guide allocations', ['only' => ['destroy']]);
    }

    public function index(GuideAllocationsDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function create()
    {
        $selectionguideallocation = new GuideAllocation();
        return view('setting.selection.guideallocation-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionguideallocation'=> $selectionguideallocation,
            ],
        ));
    }

    public function store(Request $request)
    {
        GuideAllocation::create($request->all());
        return to_route('selectionguideallocations.index')->with('success','data telah ditambahkan');
    }

    public function edit(GuideAllocation $selectionguideallocation)
    {
        return view('setting.selection.guideallocation-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionguideallocation'=> $selectionguideallocation,
            ],
        ));
    }

    public function update(Request $request, GuideAllocation $selectionguideallocation)
    {
        $data = $request->all();
        $selectionguideallocation->fill($data)->save();

        return to_route('selectionguideallocations.index')->with('success','data telah diperbarui');
    }

    public function destroy(GuideAllocation $selectionguideallocation)
    {
        $selectionguideallocation->delete();
        return to_route('selectionguideallocations.index')->with('warning','data telah dihapus');
    }

    public function activation(GuideAllocation $guideallocation)
    {
        $guideallocation->active = ($guideallocation->active ? 0 : 1);
        $guideallocation->save();
        return redirect()->back();
    }

    private function _dataSelection()
    {
        return [
            'lectures' =>  User::role('dosen')->select('name','id')->get()->sort(),
        ];
    }
}
