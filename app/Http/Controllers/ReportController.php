<?php

namespace App\Http\Controllers;

use App\Models\ExamFormItem;
use Illuminate\Http\Request;
use App\Models\ViewExamScore;
use App\Models\ExamRegistration;
use Barryvdh\DomPDF\Facade\Pdf as PDF;


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

    public function createThesisExamByChiefPDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $guidescores = $this->_guideData($examregistration->id);
        $examinerscores = $this->_examinerData($examregistration->id);
        $last_seminar_score = ExamRegistration::where('user_id',$examregistration->user_id)->where('exam_type_id',2)->latest()->first()->grade;
        $pdf = PDF::loadView('report.thesis-exam-result',compact('examregistration','examscores','guidescores','examinerscores','last_seminar_score'));
        // return view('report.thesis-exam-result',compact('examregistration','examscores'));

        return $pdf->stream('berita-acara-ujian.pdf');
    }

    public function createThesisExamByLecturePDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $form_items = ExamFormItem::select('id','name','exam_type_id')->where('exam_type_id',3)->get();
        $pdf = PDF::loadView('report.thesis-exam-by-lecture',compact('examregistration','examscores','form_items'));
        // return view('report.thesis-exam-result',compact('examregistration','examscores'));

        return $pdf->stream('penilaian-ujian.pdf');
    }

    public function createThesisRevisionByLecturePDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $pdf = PDF::loadView('report.thesis-rev-by-lecture',compact('examregistration','examscores'));
        // return view('report.thesis-exam-result',compact('examregistration','examscores'));

        return $pdf->stream('revisi-ujian.pdf');
    }

    private function _examData($examregistration_id) {
        return ViewExamScore::where('exam_registration_id',$examregistration_id)->orderBy('examiner_order')->get();
    }

    private function _guideData($examregistration_id) {
        return ViewExamScore::where('exam_registration_id',$examregistration_id)->whereIn('examiner_order',[4,5])->orderBy('examiner_order')->get();
    }

    private function _examinerData($examregistration_id) {
        return ViewExamScore::where('exam_registration_id',$examregistration_id)->whereIn('examiner_order',[1,2,3])->orderBy('examiner_order')->get();
    }
}
