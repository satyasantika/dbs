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
        $this->middleware('permission:read selection guides', ['only' => ['index','show']]);
        $this->middleware('permission:create selection guides', ['only' => ['create','store']]);
        $this->middleware('permission:update selection guides', ['only' => ['edit','update']]);
        $this->middleware('permission:delete selection guides', ['only' => ['destroy']]);
    }

    public function index($stage)
    {
        $guides = SelectionGuide::where('selection_stage_id',$stage)->oldest()->get();
        $max_guides = SelectionGuide::where('selection_stage_id',$stage)->whereNull('approved')->oldest()->count()/2;
        $available_guide1s = GuideGroup::whereNot('guide1_quota',0)->where('active',1)->orderBy('guide_allocation_id')->get();
        $available_guide2s = GuideGroup::whereNot('guide2_quota',0)->where('active',1)->orderBy('guide_allocation_id')->get();
        return view('selection.guide-submission',compact('guides','available_guide1s','available_guide2s','max_guides'));
    }

    public function create()
    {
        $guide = new SelectionGuide();
        return view('selection.guide-form', array_merge(
            $this->_dataSelection(),
            [
                'guide'=> $guide,
            ],
        ));
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

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, SelectionGuide $guide)
    {
        $data = $request->all();
        $data['selection_stage_id'] = $guide->selection_stage_id;
        $data['user_id'] = GuideGroup::find($request->guide_group_id)->allocation->user_id;
        $guide->fill($data)->save();
        $name = strtoupper(User::find($guide->user_id)->name);

        return to_route('guides.index',$guide->selection_stage_id)->with('success','pembimbing/penguji '.$name.' telah disahkan');
    }

    public function destroy(SelectionGuide $selectionguide)
    {
        $name = strtoupper(User::find($selectionguide->user_id)->name);
        $selectionguide->delete();
        return to_route('guides.index')->with('warning','usulan '.$name.' telah dihapus');
    }

    private function _dataSelection()
    {
        return [
            'groups' =>  GuideGroup::where('active',1)->orderBy('id')->get(),
        ];
    }

    public function cancel(Request $request, SelectionGuide $guide)
    {
        // dd($guide);
        $name = strtoupper(User::find($guide->user_id)->name);
        $data = $request->all();
        $data['guide_group_id'] = NULL;
        $data['user_id'] = NULL;
        $guide->fill($data)->save();
        return redirect()->back()->with('warning','usulan '.$name.' telah dibatalkan');
    }
}
