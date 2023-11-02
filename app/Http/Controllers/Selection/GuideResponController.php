<?php

namespace App\Http\Controllers\Selection;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\SelectionGuide;
use App\Models\SelectionStage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GuideResponController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:accept selection', ['only' => ['accept']]);
        $this->middleware('permission:decline selection', ['only' => ['decline']]);
    }

    // terima usulan oleh dosen
    public function accept(Request $request, SelectionGuide $guide)
    {
        $name = strtoupper(User::find($guide->stage->user_id)->name);
        $guide->update([
                'approved' => 1,
                'information' => 'oleh user',
            ]);
        // cek apakah dua pembimbing sudah setuju?
        $complete = SelectionGuide::where([
            'pair_order'=>$guide->pair_order,
            'selection_stage_id'=>$guide->selection_stage_id,
            'approved'=>1,
            ])->count();
        if ($complete == 2) {
            SelectionGuide::where([
                'selection_stage_id'=>$guide->selection_stage_id,
                ])
                ->whereNotIn('pair_order',[$guide->pair_order])
                ->update([
                    'approved' => 0,
                    'information' => 'otomatis oleh sistem karena pasangan lain sudah ditetapkan',
                ]);
            $guide1 = SelectionGuide::where([
                'pair_order'=>$guide->pair_order,
                'selection_stage_id'=>$guide->selection_stage_id,
                'guide_order'=>1,
            ])->first();
            $guide2 = SelectionGuide::where([
                'pair_order'=>$guide->pair_order,
                'selection_stage_id'=>$guide->selection_stage_id,
                'guide_order'=>2,
            ])->first();
            SelectionStage::find($guide->selection_stage_id)->update([
                'final'=>1,
                'guide1_id'=>$guide1->user_id,
                'guide2_id'=>$guide2->user_id,
            ]);
        }

        return redirect()->back()->with('success','usulan '.$name.' telah diterima');
    }

    // tolak usulan oleh dosen
    public function decline(Request $request, SelectionGuide $guide)
    {
        $name = strtoupper(User::find($guide->stage->user_id)->name);
        $guide->update([
                'approved' => 0,
                'information' => 'oleh user',
            ]);
        SelectionGuide::where([
            'pair_order'=>$guide->pair_order,
            'selection_stage_id'=>$guide->selection_stage_id,
            'guide_order'=>($guide->guide_order == 1 ? 2 : 1),
            ])->update([
                'approved' => 0,
                'information' => 'otomatis oleh sistem karena calon lain menolak',
            ]);
        return redirect()->back()->with('warning','usulan '.$name.' telah ditolak');
    }
}
