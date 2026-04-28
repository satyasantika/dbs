<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\ExamRegistration;
use App\Models\GuideExaminer;

class WelcomeController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $tanggalSekarang = $now->toDateString();
        $waktuSelesai = $now->copy()->subHour()->format('H:i:s');

        $peserta = ExamRegistration::with(['student', 'examtype', 'examiner1', 'examiner2', 'examiner3', 'guide1', 'guide2'])
            ->where(function ($q) use ($tanggalSekarang, $waktuSelesai) {
                $q->where('exam_date', '>', $tanggalSekarang)
                    ->orWhere(function ($q2) use ($tanggalSekarang, $waktuSelesai) {
                        $q2->where('exam_date', '=', $tanggalSekarang)
                            ->where('exam_time', '>=', $waktuSelesai);
                    });
            })
            ->orderBy('exam_date')
            ->orderBy('exam_time')
            ->orderBy('room')
            ->get();

        $angkatans = GuideExaminer::where('year_generation', '>=', 2019)
            ->distinct()
            ->orderBy('year_generation')
            ->pluck('year_generation');

        $rekap = $angkatans->map(function ($angkatan) use ($now) {
            $daftarSempro = ExamRegistration::join('guide_examiners as ge', 'exam_registrations.user_id', '=', 'ge.user_id')
                ->where('ge.year_generation', $angkatan)
                ->where('exam_date', '>=', $now)
                ->where('exam_type_id', 1)
                ->pluck('exam_registrations.user_id');

            $daftarSemhas = ExamRegistration::join('guide_examiners as ge', 'exam_registrations.user_id', '=', 'ge.user_id')
                ->where('ge.year_generation', $angkatan)
                ->where('exam_date', '>=', $now)
                ->where('exam_type_id', 2)
                ->pluck('exam_registrations.user_id');

            $daftarSidang = ExamRegistration::join('guide_examiners as ge', 'exam_registrations.user_id', '=', 'ge.user_id')
                ->where('ge.year_generation', $angkatan)
                ->where('exam_date', '>=', $now)
                ->where('exam_type_id', 3)
                ->pluck('exam_registrations.user_id');

            $belumDaftarSempro = GuideExaminer::where('year_generation', $angkatan)
                ->whereNull('proposal_date')
                ->whereNull('seminar_date')
                ->whereNull('thesis_date')
                ->pluck('user_id');

            $belumDaftarSemhas = GuideExaminer::where('year_generation', $angkatan)
                ->whereNull('seminar_date')
                ->whereNull('thesis_date')
                ->whereNotIn('user_id', $daftarSempro)
                ->whereNotIn('user_id', $belumDaftarSempro)
                ->pluck('user_id');

            $belumDaftarSidang = GuideExaminer::where('year_generation', $angkatan)
                ->whereNull('thesis_date')
                ->whereNotIn('user_id', $daftarSemhas)
                ->whereNotIn('user_id', $daftarSempro)
                ->whereNotIn('user_id', $belumDaftarSempro)
                ->whereNotIn('user_id', $belumDaftarSemhas)
                ->pluck('user_id');

            $sudahLulus = GuideExaminer::where('year_generation', $angkatan)
                ->whereNotNull('thesis_date')
                ->count();

            $total = GuideExaminer::where('year_generation', $angkatan)->count();

            return [
                'angkatan'         => $angkatan,
                'total'            => $total,
                'lulus'            => $sudahLulus - $daftarSidang->count(),
                'belum_lulus'      => $total - $sudahLulus + $daftarSidang->count(),
                'belum_sempro'     => $belumDaftarSempro->count() + $daftarSempro->count(),
                'belum_sempro_reg' => $daftarSempro->count(),
                'akan_semhas'      => $belumDaftarSemhas->count() + $daftarSemhas->count(),
                'akan_semhas_reg'  => $daftarSemhas->count(),
                'akan_sidang'      => $belumDaftarSidang->count() + $daftarSidang->count(),
                'akan_sidang_reg'  => $daftarSidang->count(),
            ];
        });

        return view('welcome', compact('peserta', 'rekap'));
    }
}
