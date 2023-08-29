<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Requests\PermissionRequest;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;

class PermissionController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read permissions', ['only' => ['index','show']]);
        $this->middleware('permission:create permissions', ['only' => ['create','store']]);
        $this->middleware('permission:update permissions', ['only' => ['edit','update']]);
        $this->middleware('permission:delete permissions', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('setting.permission',['permissions'=>Permission::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('setting.permission-form',['permission'=>new Permission()]);
    }

    public function store(PermissionRequest $request)
    {
        Permission::create($request->all());
        return to_route('permissions.index');
    }

    public function edit(Permission $Permission)
    {
        return view('setting.permission-form', compact('permission'));
    }

    public function update(PermissionRequest $request, Permission $Permission)
    {
        $data = $request->all();
        $permission->fill($data)->save();

        return to_route('permissions.index');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return to_route('permissions.index');
    }
}
