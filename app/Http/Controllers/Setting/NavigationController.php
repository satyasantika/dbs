<?php

namespace App\Http\Controllers\Setting;

use App\Models\Navigation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\NavigationsDataTable;

class NavigationController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read navigations', ['only' => ['index','show']]);
        $this->middleware('permission:create navigations', ['only' => ['create','store']]);
        $this->middleware('permission:update navigations', ['only' => ['edit','update']]);
        $this->middleware('permission:delete navigations', ['only' => ['destroy']]);
    }

    public function index(NavigationsDataTable $dataTable)
    {
        return $dataTable->render('layouts.setting');
    }

    public function create()
    {
        $navigation = new Navigation();
        return view('setting.navigation-form', array_merge(
            $this->_dataSelection(),
            [
                'navigation'=> $navigation,
            ],
        ));
    }

    public function store(Request $request)
    {
        $name = strtoupper($request->name);
        if ($request->parent_id > 0) {
            Navigation::find($request->parent_id)->children()->create($request->all());
        } else {
            $request->parent_id = null;
            Navigation::create($request->all());
        }
        return to_route('navigations.index')->with('success','menu '.$name.' telah ditambahkan');
    }

    public function edit(Navigation $navigation)
    {
        return view('setting.navigation-form', array_merge(
            $this->_dataSelection(),
            [
                'navigation'=> $navigation,
            ],
        ));
    }

    public function update(Request $request, Navigation $navigation)
    {
        $name = strtoupper($navigation->name);
        $data = $request->all();
        $navigation->fill($data)->save();

        return to_route('navigations.index')->with('success','menu '.$name.' telah diperbarui');
    }

    public function destroy(Navigation $navigation)
    {
        $name = strtoupper($navigation->name);
        $navigation->delete();
        return to_route('navigations.index')->with('warning','menu '.$name.' telah dihapus');
    }

    private function _dataSelection()
    {

        return [
            'parent_navs' => Navigation::whereNull('parent_id')->get(),
        ];
    }

}
