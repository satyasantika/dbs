<?php

namespace App\Http\Controllers\Selection;

use App\Models\User;
use App\Models\GuideGroup;
use App\Models\SelectionGuide;
use App\Models\SelectionStage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GuideResponController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read selection guides', ['only' => ['index']]);
        $this->middleware('permission:accept selection', ['only' => ['accept']]);
        $this->middleware('permission:decline selection', ['only' => ['decline']]);
    }

    // list proses pemilihan
    public function index()
    {
        $guides = SelectionGuide::join('selection_stages','selection_guides.selection_stage_id','=','selection_stages.id')
            ->where([
            'selection_guides.user_id'=>Auth::id(),
            'selection_stages.stage_order'=>2,
            ])
            ->select('selection_guides.*')
            ->get();

        return view('selection.guide-respon',compact('guides'));
    }

    // hasil pemilihan pada tahap 1,2,3,dst
    public function result()
    {
        $guides = SelectionGuide::join('selection_stages','selection_guides.selection_stage_id','=','selection_stages.id')
            ->where([
            'selection_guides.user_id'=>Auth::id(),
            'selection_stages.final'=>1,
            ])->get();
            // dd($guides);
        return view('selection.guide-result',compact('guides'));
    }

    // terima usulan oleh dosen
    public function accept(SelectionGuide $guide)
    {
        $name = strtoupper($guide->stage->student->name);

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
            // tetapkan pembimbing pada tahapan ini
            SelectionStage::find($guide->selection_stage_id)->update([
                'final'=>1,
                'guide1_id'=>$guide1->user_id,
                'guide2_id'=>$guide2->user_id,
            ]);


            $mygroup = SelectionGuide::where('guide_group_id',$guide->guide_group_id)->pluck('selection_stage_id');
            $filled = SelectionStage::whereIn('id',$mygroup)->where('final',1)->count();

            $guide_filled = 'guide'.$guide->guide_order.'_filled';
            GuideGroup::find($guide->guide_group_id)->update([
                $guide_filled=>$filled,
            ]);

            $guide_plan = GuideGroup::find($guide->guide_group_id);
            if ($guide->guide_order == 1) {
                $remain = $guide_plan->guide1_quota - $guide_plan->guide1_filled;
            } else {
                $remain = $guide_plan->guide2_quota - $guide_plan->guide2_filled;
            }

            if ($remain < 1) {
            SelectionGuide::whereIn('selection_stage_id',$mygroup)
                ->whereNotIn('selection_stage_id',[$guide->selection_stage_id])
                ->update([
                    'approved' => 0,
                    'information' => 'otomatis oleh sistem karena kuota telah terpenuhi',
                ]);
            }

        }

        return redirect()->back()->with('success','usulan '.$name.' telah diterima');
    }

    // tolak usulan oleh dosen
    public function decline(SelectionGuide $guide)
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

    private function _remainQuota($guide_group_id, $guide_order)
    {
        $guide = 'guide'.$guide_order.'_quota';
        $groupQuota = GuideGroup::where([
            'id'=>$guide_group_id,
            'active'=>1,
            ])->value($guide);
        $groupFilled = SelectionGuide::where([
            'guide_group_id'=>$guide_group_id,
            'guide_order'=>$guide_order,
            'approved'=>1,
            ])->count();
        return $groupQuota-$groupFilled;
    }
}
