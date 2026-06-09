<?php

namespace App\Http\Controllers\Examination;

use App\Filament\Dosen\Pages\ChiefExam;
use App\Filament\Dosen\Pages\ViewChiefExam;
use App\Models\ExamScore;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ChiefController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read scoring', ['only' => ['index','show']]);
        // $this->middleware('permission:create scoring', ['only' => ['create','store']]);
        // $this->middleware('permission:update scoring', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete scoring', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (auth()->user()->hasRole('dosen')) {
            return redirect(ChiefExam::getUrl());
        }

        $examinations = ExamRegistration::query()
            ->where('chief_id', auth()->id())
            ->get();

        return view('examination.chief', compact('examinations'));
    }

    public function show(ExamRegistration $chief)
    {
        if (auth()->user()->hasRole('dosen')) {
            return redirect(ViewChiefExam::getUrl(['record' => $chief->id]));
        }

        if ($chief->chief_id != Auth::id()) {
            return to_route('scoring.index');
        }

        $examinations = ExamScore::where('exam_registration_id', $chief->id)->get();

        return view('examination.chief', compact('examinations', 'chief'));
    }

    public function pass(ExamRegistration $chief)
    {
        $name = strtoupper($chief->student->name);

        $cek = ExamScore::where([
            'exam_registration_id' => $chief->id,
            'pass_approved' => 1,
        ])->count();

        if ($cek < 5) {
            if (auth()->user()->hasRole('admin')) {
                return to_route('examregistrations.examscores.index', $chief)->with('warning', 'tidak bisa finalisasi, masih ada nilai yang belum terinput');
            }

            if (auth()->user()->hasRole('dosen')) {
                return redirect(ViewChiefExam::getUrl(['record' => $chief->id]))
                    ->with('warning', 'tidak bisa finalisasi, masih ada nilai yang belum terinput');
            }

            return to_route('chief.show', $chief)->with('warning', 'tidak bisa finalisasi, masih ada nilai yang belum terinput');
        }

        $chief->pass_exam = 1;
        $chief->save();

        if (auth()->user()->hasRole('admin')) {
            return to_route('examregistrations.examscores.index', $chief)->with('success', 'mahasiswa '.$name.' telah layak dilanjutkan');
        }

        if (auth()->user()->hasRole('dosen')) {
            return redirect(ViewChiefExam::getUrl(['record' => $chief->id]))
                ->with('success', 'mahasiswa '.$name.' telah layak dilanjutkan');
        }

        return to_route('chief.show', $chief)->with('success', 'mahasiswa '.$name.' telah layak dilanjutkan');
    }
}
