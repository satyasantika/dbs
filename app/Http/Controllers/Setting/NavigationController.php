<?php

namespace App\Http\Controllers\Setting;

use App\Models\Navigation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NavigationController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read setting/navigations', ['only' => ['index','show']]);
        $this->middleware('permission:create setting/navigations', ['only' => ['create','store']]);
        $this->middleware('permission:update setting/navigations', ['only' => ['edit','update']]);
        $this->middleware('permission:delete setting/navigations', ['only' => ['destroy']]);
    }

    public function index()
    {

        return view('setting.navigation',['navigations'=>Navigation::orderBy('order')->get()]);
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
        if ($request->parent_id > 0) {
            Navigation::find($request->parent_id)->children()->create($request->all());
        } else {
            $request->parent_id = null;
            Navigation::create($request->all());
        }
        return to_route('navigations.index');
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
        $data = $request->all();
        $navigation->fill($data)->save();

        return to_route('navigations.index');
    }

    public function destroy(Navigation $navigation)
    {
        $name = $navigation->name;
        $navigation->delete();
        return to_route('navigations.index');
    }

    private function _dataSelection()
    {

        return [
            'parent_navs' => Navigation::whereNull('parent_id')->get(),
        ];
    }

}
