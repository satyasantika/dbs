<?php

namespace App\Http\Controllers\Selection;

use App\Models\User;
use App\Models\GuideGroup;
use App\Models\SelectionGuide;
use App\Models\SelectionStage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;;

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
            ->orderBy('selection_guides.updated_at','DESC')
            ->get();

        return view('selection.guide-respon',compact('guides'));
    }

    // hasil pemilihan pada tahap 1,2,3,dst
    public function result()
    {
        $guides = SelectionStage::with('guide1','guide2','student')->where('guide1_id',Auth::id())->orWhere('guide2_id',Auth::id())->where('final',1)->get();
        return view('selection.guide-result',compact('guides'));
    }

    // terima usulan oleh dosen
    public function accept(SelectionGuide $guide)
    {
        $name = strtoupper($guide->stage->student->name);
        // guide group pasangan
        $guide_pair = SelectionGuide::where([
            'selection_stage_id'=>$guide->selection_stage_id,
            'pair_order'=>$guide->pair_order,
            'guide_order'=>$guide->guide_order == 1 ? 2 : 1,
        ]);
        // guide grup saya
        $guide_grup_id_1 = $guide->guide_group_id;
        $guide_grup_id_2 = $guide_pair->first()->guide_group_id;
        $guide_order1 = $guide->guide_order;
        $guide_order2 = $guide_pair->first()->guide_order;

        $remain1 = $this->_remainQuota($guide_grup_id_1,$guide_order1);
        $remain2 = $this->_remainQuota($guide_grup_id_2,$guide_order2);

        // jika kuota pembimbing sudah habis
        if ($remain1 < 1 || $remain2 < 1) {
            if ($remain1 < 1) {
                $information_me = 'sistem menolak karena kuota habis';
                $information_pair = 'sistem menolak karena kuota pasangan calon pembimbing sudah habis';
            } else {
                $information_me = 'sistem menolak karena kuota pasangan calon pembimbing sudah habis';
                $information_pair = 'sistem menolak karena kuota habis';
            }
            // update tolak usulan calon pembimbing
            $guide->update([
                'approved' => 0,
                'information' => $information_me,
            ]);
            // update tolak usulan pasangan calon pembimbingnya
            $guide_pair->update([
                    'approved' => 0,
                    'information' => $information_pair,
                ]);
            return redirect()->back()->with('warning','usulan '.$name.' telah ditolak');

        } else {
            // update terima usulan calon pembimbing
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
                // pasangan lain otomatis ditolak sistem
                SelectionGuide::where('selection_stage_id',$guide->selection_stage_id)
                    ->whereNotIn('pair_order',[$guide->pair_order])
                    ->update([
                        'approved' => 0,
                        'information' => 'sistem menolak karena pasangan lain sudah ditetapkan',
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
                    'guidegroup1_id'=>$guide1->guide_group_id,
                    'guidegroup2_id'=>$guide2->guide_group_id,
                ]);
                if ($guide->guide_order == 1) {
                    $guidegroup = SelectionStage::where('guidegroup1_id',$guide1->guide_group_id)->count();
                    GuideGroup::find($guide1->guide_group_id)->update([
                            'guide1_filled'=>$guidegroup,
                        ]);
                } else {
                    $guidegroup = SelectionStage::where('guidegroup2_id',$guide2->guide_group_id)->count();
                    GuideGroup::find($guide2->guide_group_id)->update([
                            'guide2_filled'=>$guidegroup,
                        ]);
                }
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
        // pasangannya otomatis ditolak juga
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

    private function _remainQuota($guide_group_id,$guide_order)
    {
        $mygroup = GuideGroup::find($guide_group_id);
        if ($guide_order == 1) {
            $remain = $mygroup->guide1_quota - $mygroup->guide1_filled;
        } else {
            $remain = $mygroup->guide2_quota - $mygroup->guide2_filled;
        }
        return $remain;
    }

    // membatalkan usulan
    // public function retract(SelectionGuide $guide)
    // {
    //     $name = strtoupper(User::find($guide->stage->user_id)->name);
    //     $stage = SelectionStage::where('id',$guide->selection_stage_id)
    //                 ->where(function (Builder $query, SelectionGuide $guide) {
    //                     $query->where('guidegroup1_id',$guide->guide_group_id)
    //                             ->orWhere('guidegroup2_id',$guide->guide_group_id);
    //                 });
    //     if ($stage->exists()) {
    //         $stage->update([
    //             'final' => 0,
    //             'guidegroup1_id' => NULL,
    //             'guidegroup2_id' => NULL,
    //         ]);
    //     }
    //     $guide->update([
    //             'approved' => NULL,
    //             'information' => NULL,
    //         ]);
    //     return redirect()->back()->with('warning','keputusan usulan '.$name.' telah dibatalkan');
    // }
}
