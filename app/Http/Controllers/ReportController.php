<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
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
        $filename = 'hasil-revisi-';
        $filename = $filename.$examregistration->examtype->code.'-';
        $filename = Str::slug($filename.$examregistration->student->name).'.pdf';
        // return view('report.revision-table',compact('examregistration','examscores'));

        return $pdf->stream($filename);
    }

    public function createRevisionSignPDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $pdf = PDF::loadView('report.revision-sign',compact('examregistration','examscores'));
        $filename = 'surat-keterangan-revisi-';
        $filename = $filename.$examregistration->examtype->code.'-';
        $filename = Str::slug($filename.$examregistration->student->name).'.pdf';
        // return view('report.revision-sign',compact('examregistration','examscores'));

        return $pdf->stream($filename);
    }

    public function createExamByChiefPDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $pdf = PDF::loadView('report.exam-result',compact('examregistration','examscores'));
        $filename = 'hasil-ujian-';
        $filename = $filename.$examregistration->examtype->code.'-';
        $filename = Str::slug($filename.$examregistration->student->name).'.pdf';
        // return view('report.exam-result',compact('examregistration','examscores'));

        return $pdf->stream($filename);
    }

    public function createThesisExamByChiefPDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $guidescores = $this->_guideData($examregistration->id);
        $examinerscores = $this->_examinerData($examregistration->id);
        $last_seminar_score = ExamRegistration::where('user_id',$examregistration->user_id)->where('exam_type_id',2)->latest()->first()->grade;
        $pdf = PDF::loadView('report.thesis-exam-result',compact('examregistration','examscores','guidescores','examinerscores','last_seminar_score'));
        $filename = 'berita-acara-ujian-';
        $filename = $filename.$examregistration->examtype->code.'-';
        $filename = Str::slug($filename.$examregistration->student->name).'.pdf';
        // return view('report.thesis-exam-result',compact('examregistration','examscores'));

        return $pdf->stream($filename);
    }

    public function createThesisExamByLecturePDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $form_items = ExamFormItem::select('id','name','exam_type_id')->where('exam_type_id',3)->get();
        $pdf = PDF::loadView('report.thesis-exam-by-lecture',compact('examregistration','examscores','form_items'));
        $filename = 'penilaian-ujian-';
        $filename = $filename.$examregistration->examtype->code.'-';
        $filename = Str::slug($filename.$examregistration->student->name).'.pdf';
        // return view('report.thesis-exam-result',compact('examregistration','examscores'));

        return $pdf->stream($filename);
    }

    public function createThesisRevisionByLecturePDF(ExamRegistration $examregistration)
    {
        $examscores = $this->_examData($examregistration->id);
        $pdf = PDF::loadView('report.thesis-rev-by-lecture',compact('examregistration','examscores'));
        $filename = 'revisi-ujian-';
        $filename = $filename.$examregistration->examtype->code.'-';
        $filename = Str::slug($filename.$examregistration->student->name).'.pdf';
        // return view('report.thesis-exam-result',compact('examregistration','examscores'));

        return $pdf->stream($filename);
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
