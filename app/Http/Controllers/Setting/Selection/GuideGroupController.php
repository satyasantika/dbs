<?php

namespace App\Http\Controllers\Setting\Selection;

use App\Models\User;
use App\Models\GuideGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\GuideGroupsDataTable;

class GuideGroupController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read guide groups', ['only' => ['index','show']]);
        $this->middleware('permission:create guide groups', ['only' => ['create','store']]);
        $this->middleware('permission:update guide groups', ['only' => ['edit','update']]);
        $this->middleware('permission:delete guide groups', ['only' => ['destroy']]);
    }

    public function index(GuideGroupsDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function create()
    {
        $selectionguidegroup = new GuideGroup();
        return view('setting.selection.guidegroup-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionguidegroup'=> $selectionguidegroup,
            ],
        ));
    }

    public function store(Request $request)
    {
        GuideGroup::create($request->all());
        return to_route('selectionguidegroups.index')->with('success','data telah ditambahkan');
    }

    public function edit(GuideGroup $selectionguidegroup)
    {
        return view('setting.selection.guidegroup-form', array_merge(
            $this->_dataSelection(),
            [
                'selectionguidegroup'=> $selectionguidegroup,
            ],
        ));
    }

    public function update(Request $request, GuideGroup $selectionguidegroup)
    {
        $data = $request->all();
        $selectionguidegroup->fill($data)->save();

        return to_route('selectionguidegroups.index')->with('success','data telah diperbarui');
    }

    public function destroy(GuideGroup $selectionguidegroup)
    {
        $selectionguidegroup->delete();
        return to_route('selectionguidegroups.index')->with('warning','data telah dihapus');
    }

    public function activation(GuideGroup $guidegroup)
    {
        $guidegroup->active = ($guidegroup->active ? 0 : 1);
        $guidegroup->save();
        return redirect()->back();
    }

    private function _dataSelection()
    {
        return [
            'lectures' =>  User::role('dosen')->select('name','id')->get()->sort(),
        ];
    }
}
