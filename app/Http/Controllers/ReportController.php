<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;
use App\Models\ViewExamScore;
use App\Models\ExamRegistration;


class ReportController extends Controller
{
    public function createRevisionTablePDF(ExamRegistration $examregistration)
    {
        ini_set('max_execution_time', 300);
        ini_set("memory_limit","512M");

        $examscores = $this->_examData($examregistration->id);
        $pdf = PDF::loadView('report.revision-table',compact('examregistration','examscores'));
        // return view('report.revision-table',compact('examregistration','examscores'));

        return $pdf->stream('lembar-revisi.pdf');
    }

    public function createRevisionSignPDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $pdf = PDF::loadView('report.revision-sign',compact('examregistration','examscores'));
        // return view('report.revision-sign',compact('examregistration','examscores'));

        return $pdf->stream('keterangan-revisi.pdf');
    }

    public function createExamByChiefPDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $pdf = PDF::loadView('report.exam-result',compact('examregistration','examscores'));
        // return view('report.exam-result',compact('examregistration','examscores'));

        return $pdf->stream('hasil-ujian.pdf');
    }

    private function _examData($examregistration_id) {
        return ViewExamScore::where('exam_registration_id',$examregistration_id)->orderBy('examiner_order')->get();
    }
}
