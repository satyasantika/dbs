<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read roles', ['only' => ['index','show']]);
        $this->middleware('permission:create roles', ['only' => ['create','store']]);
        $this->middleware('permission:update roles', ['only' => ['edit','update']]);
        $this->middleware('permission:delete roles', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('setting.role',['roles'=>Role::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('setting.role-form',['role'=>new Role()]);
    }

    public function store(RoleRequest $request)
    {
        Role::create($request->all());
        return to_route('roles.index');
    }

    public function edit(Role $role)
    {
        return view('setting.role-form', compact('role'));
    }

    public function update(RoleRequest $request, Role $role)
    {
        $data = $request->all();
        $role->fill($data)->save();

        return to_route('roles.index');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return to_route('roles.index');
    }
}
