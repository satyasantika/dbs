<?php

namespace App\Http\Controllers\Selection;

use Illuminate\Http\Request;
use App\Models\SelectionStage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class StageController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:create selection stages', ['only' => ['store']]);
    }

    public function store(Request $request)
    {
        SelectionStage::create([
            'user_id' => Auth::id(),
            'stage_order' => 2, //to FIX khusus tahap 2
        ]);
        return redirect()->back()->with('success','usulanmu telah ditambahkan');
    }

}
