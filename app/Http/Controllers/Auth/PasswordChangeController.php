<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordChangeController extends Controller
{
    public function showChangePasswordGet() {
        return view('auth.passwords.change');
    }

    public function changePasswordPost(Request $request) {
        if (!(Hash::check($request->get('current-password'), Auth::user()->password))) {
            // The passwords matches
            return redirect()->back()->with("error","Anda salah menginputkan password saat ini.");
        }

        if(strcmp($request->get('current-password'), $request->get('new-password')) == 0){
            // Current password and new password same
            return redirect()->back()->with("error","Password baru jangan sama dengan password saat ini.");
        }

        $validatedData = $request->validate([
            'current-password' => 'required',
            'new-password' => 'required|string|min:4|confirmed',
        ]);

        //Change Password
        $user = Auth::user();
        $user->password = bcrypt($request->get('new-password'));
        $user->save();

        return redirect()->back()->with("success","Password sudah diubah!");
    }

    public function resetPasswordPost($id)
    {
        //Change Password
        $user = User::find($id);
        $user->password = bcrypt($user->username);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password untuk <strong>'.$user->name.'</strong> telah direset'
        ]);
    }
}
