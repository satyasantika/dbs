<?php

namespace App\Http\Controllers\Setting\Examination;

use App\Models\User;
use App\Models\ExamType;
use App\Models\ExamScore;
use Illuminate\Http\Request;
use App\Models\GuideExaminer;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use App\DataTables\ExamRegistrationsDataTable;

class ExamRegistrationController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read exam registrations', ['only' => ['index', 'index2']]);
        // $this->middleware('permission:create examregistrations', ['only' => ['create','store']]);
        $this->middleware('permission:update exam registrations', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:delete examregistrations', ['only' => ['destroy']]);
    }

    public function index(ExamRegistrationsDataTable $dataTable)
    {
        $title = 'Jadwal Ujian';
        return $dataTable->render('layouts.setting', compact('title'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'            => 'required|integer|exists:users,id',
            'exam_type_id'       => 'required|integer|exists:exam_types,id',
            'registration_order' => 'required|integer|in:1,2,3',
            'exam_date'          => 'required|date',
            'exam_time'          => 'required',
            'room'               => 'required',
            'title'              => 'required|string',
            'ipk'                => 'nullable|numeric|min:2|max:4',
            'chief_id'           => 'nullable|integer|exists:users,id',
            'exam_file'          => 'nullable|string',
        ]);

        $guideexaminer = GuideExaminer::where('user_id', $validated['user_id'])->first();

        if (!$guideexaminer) {
            return back()
                ->withErrors(['user_id' => 'Data penguji mahasiswa tidak ditemukan di sistem.'])
                ->withInput();
        }

        ExamRegistration::create(array_merge($validated, [
            'examiner1_id' => $guideexaminer->examiner1_id,
            'examiner2_id' => $guideexaminer->examiner2_id,
            'examiner3_id' => $guideexaminer->examiner3_id,
            'guide1_id'    => $guideexaminer->guide1_id,
            'guide2_id'    => $guideexaminer->guide2_id,
        ]));

        $tanggalUjian = match((int) $validated['exam_type_id']) {
            1       => 'proposal_date',
            2       => 'seminar_date',
            3       => 'thesis_date',
            default => null,
        };

        if ($tanggalUjian) {
            $guideexaminer->update([$tanggalUjian => $validated['exam_date']]);
        }

        $student = User::find($validated['user_id']);
        $student->givePermissionTo('join exam');
        $name = strtoupper($student->name ?? '');

        return $this->showByStudent($validated['user_id'])->with('success', 'Pendaftaran ujian ' . $name . ' telah ditambahkan');
    }

    public function edit(ExamRegistration $examregistration)
    {
        $chiefs = User::whereIn('id', array_filter([
            $examregistration->examiner1_id,
            $examregistration->examiner2_id,
            $examregistration->examiner3_id,
            $examregistration->guide1_id,
            $examregistration->guide2_id,
        ]))->role('dosen')->select('initial', 'name', 'id')->get()->sort();

        $exam_score_set = ExamScore::where('exam_registration_id', $examregistration->id)->exists();

        return view('setting.examination.examregistration-form', array_merge(
            $this->_dataSelection(),
            [
                'examregistration' => $examregistration,
                'chiefs'           => $chiefs,
                'exam_score_set'   => $exam_score_set,
            ],
        ));
    }

    public function update(Request $request, ExamRegistration $examregistration)
    {
        $validated = $request->validate([
            'exam_type_id'       => 'required|integer|exists:exam_types,id',
            'registration_order' => 'required|integer|in:1,2,3',
            'exam_date'          => 'required|date',
            'exam_time'          => 'required',
            'room'               => 'required',
            'title'              => 'required|string',
            'ipk'                => 'nullable|numeric|min:2|max:4',
            'exam_file'          => 'nullable|string',
            // Penguji — hanya di-submit saat exam_score_set false (tidak disabled)
            'examiner1_id'       => 'sometimes|nullable|integer|exists:users,id',
            'examiner2_id'       => 'sometimes|nullable|integer|exists:users,id',
            'examiner3_id'       => 'sometimes|nullable|integer|exists:users,id',
            'guide1_id'          => 'sometimes|nullable|integer|exists:users,id',
            'guide2_id'          => 'sometimes|nullable|integer|exists:users,id',
            'chief_id'           => 'sometimes|nullable|integer|exists:users,id',
            // Accordion optional (edit only)
            'schedule_link'      => 'sometimes|nullable|string',
            'online_link'        => 'sometimes|nullable|string',
            'online_user'        => 'sometimes|nullable|string',
            'online_password'    => 'sometimes|nullable|string',
        ]);

        $examregistration->update($validated);

        $tanggalUjian = match((int) $examregistration->exam_type_id) {
            1       => 'proposal_date',
            2       => 'seminar_date',
            3       => 'thesis_date',
            default => null,
        };

        if ($tanggalUjian) {
            $geUpdate = [$tanggalUjian => $examregistration->exam_date];

            // Sinkronisasi penguji ke guide_examiners hanya jika field-nya di-submit
            if (array_key_exists('examiner1_id', $validated)) {
                $geUpdate += [
                    'examiner1_id' => $examregistration->examiner1_id,
                    'examiner2_id' => $examregistration->examiner2_id,
                    'examiner3_id' => $examregistration->examiner3_id,
                    'guide1_id'    => $examregistration->guide1_id,
                    'guide2_id'    => $examregistration->guide2_id,
                ];
            }

            GuideExaminer::where('user_id', $examregistration->user_id)->update($geUpdate);
        }

        $name = strtoupper($examregistration->student->name ?? '');
        return $this->showByStudent($examregistration->user_id)->with('success', 'Data pendaftaran ' . $name . ' telah diperbarui');
    }

    public function destroy(ExamRegistration $examregistration)
    {
        $name = strtoupper($examregistration->student->name ?? '');
        $studentId = $examregistration->user_id;
        $examregistration->delete();

        return $this->showByStudent($studentId)->with('warning', 'Pendaftaran ' . $name . ' telah dihapus');
    }

    public function scoreSet(ExamRegistration $examregistration)
    {
        ExamScore::create([
            'exam_registration_id' => $examregistration->id,
            'user_id'              => $examregistration->examiner1_id,
            'examiner_order'       => 1,
        ]);
        ExamScore::create([
            'exam_registration_id' => $examregistration->id,
            'user_id'              => $examregistration->examiner2_id,
            'examiner_order'       => 2,
        ]);
        ExamScore::create([
            'exam_registration_id' => $examregistration->id,
            'user_id'              => $examregistration->examiner3_id,
            'examiner_order'       => 3,
        ]);
        ExamScore::create([
            'exam_registration_id' => $examregistration->id,
            'user_id'              => $examregistration->guide1_id,
            'examiner_order'       => 4,
        ]);
        ExamScore::create([
            'exam_registration_id' => $examregistration->id,
            'user_id'              => $examregistration->guide2_id,
            'examiner_order'       => 5,
        ]);

        return redirect()->back()->with('success', 'Data para penguji telah ditambahkan');
    }

    public function createByStudent($student_id)
    {
        $student = User::find($student_id);
        $chiefs = User::role('dosen')->select('initial', 'name', 'id')->get()->sort();
        $examregistration = new ExamRegistration();

        return view('setting.examination.examregistration-form', array_merge(
            [
                'student'       => $student,
                'examregistration' => $examregistration,
                'exam_score_set'   => false,
                'chiefs'           => $chiefs,
            ],
            $this->_dataSelection(),
        ));
    }

    public function showByStudent($student_id)
    {
        $student = User::find($student_id);
        $examregistrations = ExamRegistration::with(['examtype', 'examiner1', 'examiner2', 'examiner3', 'guide1', 'guide2'])
            ->where('user_id', $student_id)
            ->orderBy('exam_date')
            ->get();

        return view('examination.examregistration', compact('examregistrations', 'student'));
    }

    private function _dataSelection()
    {
        $pass_students = GuideExaminer::whereNull('thesis_date')->pluck('user_id');
        return [
            'students'   => User::role('mahasiswa')->select('name', 'id', 'username')->whereIn('id', $pass_students)->get()->sort(),
            'lectures'   => User::role('dosen')->select('initial', 'name', 'id')->get()->sort(),
            'exam_types' => ExamType::select('name', 'id')->get(),
        ];
    }

    public function index2(ExamRegistrationsDataTable $dataTable, $id = "")
    {
        return $dataTable->with('user_id', $id)->render('layouts.setting');
    }
}
