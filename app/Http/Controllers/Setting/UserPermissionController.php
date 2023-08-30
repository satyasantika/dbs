<?php

namespace App\Http\Controllers\Setting;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:update roles|update users', ['only' => ['edit','update']]);
    }

    public function edit($id)
    {
        $user = User::find($id);
        $permissions = Permission::orderBy('name')->pluck('name','id');
        $userPermissions = $user->permissions->pluck('id','id')->all();

        return view('setting.userpermission-form',compact('user','permissions','userPermissions'));
    }

    public function update(Request $request, $id)
    {
        DB::table('model_has_permissions')->where('model_id',$id)->delete();
        User::find($id)->givePermissionTo($request->permission);

        return to_route('users.index');
    }
}
