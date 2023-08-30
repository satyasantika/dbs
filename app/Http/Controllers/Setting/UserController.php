<?php

namespace App\Http\Controllers\Setting;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read users', ['only' => ['index','show']]);
        $this->middleware('permission:create users', ['only' => ['create','store']]);
        $this->middleware('permission:update users', ['only' => ['edit','update']]);
        $this->middleware('permission:delete users', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('setting.user',['users'=>User::orderBy('name')->get()]);
    }

    public function create()
    {
        $user = new User();
        return view('setting.user-form',array_merge(
            [ 'user' => $user ],
            $this->_dataSelection(),
        ));
    }

    public function store(UserRequest $request)
    {
        $data = $request->safe()->merge([
            'password'=> bcrypt($request->password),
        ]);
        User::create($data->all())->assignRole($request->role);
        return to_route('users.index');
    }

    public function edit(User $user)
    {
        return view('setting.user-form',array_merge(
            [ 'user' => $user ],
            $this->_dataSelection(),
        ));
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->all();
        $user->fill($data)->save();

        return to_route('users.index');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return to_route('users.index');
    }

    public function activation(User $user)
    {
        $user->na = $user->na ? 0 : 1;
        $user->save();
        return to_route('users.index');
    }

    private function _dataSelection()
    {
        return [
            'roles' =>  Role::all()->pluck('name')->sort(),
        ];
    }
}
