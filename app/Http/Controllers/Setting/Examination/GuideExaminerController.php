<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\GuideExaminer;
use App\Http\Controllers\Controller;
use App\DataTables\GuideExaminersDataTable;

class GuideExaminerController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read guideexaminers', ['only' => ['index','show']]);
        // $this->middleware('permission:create guideexaminers', ['only' => ['create','store']]);
        // $this->middleware('permission:update guideexaminers', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete guideexaminers', ['only' => ['destroy']]);
    }

    public function index(GuideExaminersDataTable $dataTable)
    {
        $title = 'Data Ujian';
        return $dataTable->render('layouts.setting', compact('title'));
    }

    public function create()
    {
        $guideexaminer = new GuideExaminer();
        return view('setting.examination.guideexaminer-form', array_merge(
            $this->_dataSelection(),
            ['guideexaminer' => $guideexaminer],
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'         => 'required|integer|exists:users,id',
            'year_generation' => 'required|integer',
            'examiner1_id'    => 'required|integer|exists:users,id',
            'examiner2_id'    => 'required|integer|exists:users,id',
            'examiner3_id'    => 'required|integer|exists:users,id',
            'guide1_id'       => 'required|integer|exists:users,id',
            'guide2_id'       => 'required|integer|exists:users,id',
            'proposal_date'   => 'nullable|date',
            'seminar_date'    => 'nullable|date',
            'thesis_date'     => 'nullable|date',
        ]);

        $guideexaminer = GuideExaminer::create($validated);
        $guideexaminer->load('student');
        $name = strtoupper($guideexaminer->student->name ?? '');

        return to_route('guideexaminers.index')->with('success', 'Data ' . $name . ' telah ditambahkan');
    }

    public function edit(GuideExaminer $guideexaminer)
    {
        return view('setting.examination.guideexaminer-form', array_merge(
            $this->_dataSelection(),
            ['guideexaminer' => $guideexaminer],
        ));
    }

    public function update(Request $request, GuideExaminer $guideexaminer)
    {
        $validated = $request->validate([
            'examiner1_id'  => 'required|integer|exists:users,id',
            'examiner2_id'  => 'required|integer|exists:users,id',
            'examiner3_id'  => 'required|integer|exists:users,id',
            'guide1_id'     => 'required|integer|exists:users,id',
            'guide2_id'     => 'required|integer|exists:users,id',
            'proposal_date' => 'nullable|date',
            'seminar_date'  => 'nullable|date',
            'thesis_date'   => 'nullable|date',
        ]);

        $guideexaminer->update($validated);
        $name = strtoupper($guideexaminer->student->name ?? '');

        return to_route('guideexaminers.index')->with('success', 'Data ' . $name . ' telah diperbarui');
    }

    public function destroy(GuideExaminer $guideexaminer)
    {
        $name = strtoupper($guideexaminer->student->name ?? '');
        $guideexaminer->delete();

        return to_route('guideexaminers.index')->with('warning', 'Data ' . $name . ' telah dihapus');
    }

    private function _dataSelection()
    {
        $available_students = GuideExaminer::pluck('user_id');
        return [
            'students' => User::role(['mahasiswa'])->select('name', 'id')->whereNotIn('id', $available_students)->get()->sort(),
            'lectures' => User::role('dosen')->select('initial', 'name', 'id')->get()->sort(),
        ];
    }
}
