<?php

namespace App\Http\Controllers\Selection;

use App\Models\User;
use App\Models\GuideGroup;
use Illuminate\Http\Request;
use App\Models\SelectionGuide;
use App\Models\SelectionStage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GuideController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read selection guides', ['only' => ['index']]);
        $this->middleware('permission:create selection guides', ['only' => ['store']]);
        $this->middleware('permission:update selection guides', ['only' => ['update']]);
        $this->middleware('permission:delete selection guides', ['only' => ['cancel']]);
    }

    public function index($stage)
    {
        $available_empty_guide = SelectionGuide::where('selection_stage_id',$stage)->whereNull('guide_group_id')->exists();
        $guides = SelectionGuide::where('selection_stage_id',$stage)->oldest()->get();
        $max_guides = SelectionGuide::where('selection_stage_id',$stage)->whereNull('approved')->count()/2;
        $available_guide1s = GuideGroup::where('guide1_quota','!=',0)->where('active',1)->orderBy('guide_allocation_id')->get();
        $available_guide2s = GuideGroup::where('guide2_quota','!=',0)->where('active',1)->orderBy('guide_allocation_id')->get();
        $stage = SelectionStage::find($stage);
        return view('selection.guide-submission',compact('guides','available_guide1s','available_guide2s','max_guides','stage','available_empty_guide'));
    }

    public function store(Request $request)
    {
        $stage = SelectionStage::where('user_id',Auth::id())->first();
        $input = $request->all();
        $input['selection_stage_id'] = $stage->id;
        $input['pair_order'] = SelectionGuide::where('selection_stage_id',$stage->id)->count()/2+1;
        $input['guide_order'] = 1;
        SelectionGuide::create($input);
        $input['guide_order'] = 2;
        SelectionGuide::create($input);
        return redirect()->back()->with('success','usulanmu telah ditambahkan');
    }

    public function update(Request $request, SelectionGuide $guide)
    {
        $guide_plan = GuideGroup::find($request->guide_group_id);
        if ($guide->guide_order == 1) {
            $remain = $guide_plan->guide1_quota - $guide_plan->guide1_filled;
        } else {
            $remain = $guide_plan->guide2_quota - $guide_plan->guide2_filled;
        }

        if ($remain < 1) {
            return to_route('guides.index',$guide->selection_stage_id)->with('warning','usulan pembimbing telah dibatalkan karena Kuota HABIS, silakan pilih yang lain');
        }

        $data = $request->all();
        $data['user_id'] = GuideGroup::find($request->guide_group_id)->allocation->user_id;
        $guide->fill($data)->save();
        $name = strtoupper(User::find($guide->user_id)->name);

        return to_route('guides.index',$guide->selection_stage_id)->with('success','usulan pembimbing '.$name.' telah diusulkan');
    }

    // batalkan usulan oleh mahasiswa
    public function cancel(Request $request, SelectionGuide $guide)
    {
        $name = strtoupper(User::find($guide->user_id)->name);
        $data = $request->all();
        $data['guide_group_id'] = NULL;
        $data['user_id'] = NULL;
        $guide->fill($data)->save();
        return redirect()->back()->with('warning','usulan '.$name.' telah dibatalkan');
    }
}
