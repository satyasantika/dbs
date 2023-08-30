<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read profiles', ['only' => ['index']]);
        // $this->middleware('permission:update profiles', ['only' => ['edit','update']]);
    }

    public function index()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    public function edit(User $profile)
    {
        return view('profile.profile-form', [
            'user' => $profile,
        ]);
    }

    public function update(Request $request, User $profile)
    {
        $data = $request->all();
        $profile->fill($data)->save();
        return to_route('profiles.index');
    }

}
