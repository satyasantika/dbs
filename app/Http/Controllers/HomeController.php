<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect('/admin');
        }

        if ($user->hasRole('dosen')) {
            return redirect('/home');
        }

        if ($user->hasRole('dbs')) {
            return redirect('/dbs');
        }

        if ($user->hasRole('mahasiswa')) {
            return redirect('/mahasiswa');
        }

        if ($user->hasRole('manajer nuir')) {
            return redirect('/nuir-manajer');
        }

        if ($user->hasRole('validator nuir')) {
            return redirect('/nuir-validator');
        }

        return view('home');
    }
}
