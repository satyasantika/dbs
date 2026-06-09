<?php

namespace App\Http\Controllers\Examination;

use App\Filament\Dosen\Pages\EditScoring;
use App\Filament\Dosen\Pages\Scoring;
use App\Filament\Dosen\Pages\UnscoredScoring;
use App\Models\ExamScore;
use App\Models\ExamFormItem;
use Illuminate\Http\Request;
use App\Models\ExamRegistration;
use App\DataTables\ScoringDataTable;
use App\Filament\Resources\ExamRegistrationResource;
use App\Http\Controllers\Controller;
use App\Services\Examination\ExamScoreUpdater;
use App\Services\Examination\ScoringFormPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ScoreController extends Controller
{
    function __construct()
    {
        // $this->middleware('permission:read scoring', ['only' => ['index','show']]);
        // $this->middleware('permission:create scoring', ['only' => ['create','store']]);
        // $this->middleware('permission:update scoring', ['only' => ['edit','update']]);
        // $this->middleware('permission:delete scoring', ['only' => ['destroy']]);
    }

    public function index(ScoringDataTable $dataTable)
    {
        if (auth()->user()->hasRole('dosen')) {
            return redirect(UnscoredScoring::getUrl());
        }

        $title = 'Penilaian Ujian';
        return $dataTable->render('layouts.setting',compact('title'));
    }

    public function archieves()
    {
        $exam_dates = ExamRegistration::join('exam_scores AS es', 'exam_registrations.id', '=', 'es.exam_registration_id')
            ->where('es.user_id', auth()->id())
            ->whereNotNull('es.grade')
            ->orderBy('exam_registrations.exam_date', 'desc')
            ->distinct()
            ->pluck('exam_registrations.exam_date');
        return view('examination.scoring-archieves',compact('exam_dates'));
    }

    public function edit(ExamScore $scoring)
    {
        if ($scoring->user_id != Auth::id() && ! auth()->user()->can('force edit score')) {
            return to_route('scoring.index');
        }

        if (auth()->user()->hasRole('dosen')) {
            $from = request()->query('from') === 'archive' ? '?from=archive' : '';

            return redirect(EditScoring::getUrl(['record' => $scoring->id]).$from);
        }

        $examregistration = ExamRegistration::find($scoring->exam_registration_id);
        $scoring->loadMissing(['registration.student', 'registration.examtype', 'lecture']);
        $form_items = ExamFormItem::select('id', 'name', 'exam_type_id')
            ->where('exam_type_id', $examregistration->exam_type_id)
            ->get();

        $formData = app(ScoringFormPresenter::class)->present($scoring, $examregistration, $form_items);

        return view('examination.scoring-form', [
            'formData' => $formData,
            'scoring' => $scoring,
            'returnUrl' => $this->scoringReturnUrl(request()),
        ]);
    }

    public function update(Request $request, ExamScore $scoring, ExamScoreUpdater $updater, ScoringFormPresenter $presenter)
    {
        if ($scoring->user_id != Auth::id() && ! auth()->user()->can('force edit score')) {
            return to_route('scoring.index');
        }

        $returnUrl = $this->scoringReturnUrl($request);

        if (auth()->user()->hasRole('dosen')) {
            $examRegistration = ExamRegistration::query()->findOrFail($scoring->exam_registration_id);
            $examStartAt = Carbon::parse(
                $examRegistration->exam_date->format('Y-m-d').' '.trim((string) $examRegistration->exam_time)
            );

            if ($presenter->isDosenScoringLocked($scoring, $examStartAt)) {
                return redirect($returnUrl)
                    ->with('warning', 'Penilaian sudah dikunci dan tidak dapat diubah.');
            }
        }

        $validated = $request->validate([
            'revision' => ['required', 'in:0,1'],
            'revision_note' => ['nullable', 'string'],
        ]);

        if ((int) $validated['revision'] === 1 && blank($request->input('revision_note'))) {
            throw ValidationException::withMessages([
                'revision_note' => 'Catatan revisi wajib diisi jika mahasiswa perlu revisi.',
            ]);
        }

        $payload = $request->all();
        if ((int) $validated['revision'] === 0) {
            $payload['revision_note'] = null;
        }

        $studentName = strtoupper($scoring->registration?->student?->name ?? 'MAHASISWA');
        $updater->update($scoring, $payload);

        return redirect($returnUrl)
            ->with('success', 'data penilaian '.$studentName.' telah diperbarui');
    }

    private function scoringReturnUrl(Request $request): string
    {
        if (auth()->user()->hasRole('admin')) {
            return ExamRegistrationResource::getUrl();
        }

        if (auth()->user()->hasRole('dosen')) {
            return $this->validatedDosenReturnUrl($request->input('return_url'));
        }

        return route('scoring.index');
    }

    private function validatedDosenReturnUrl(?string $url): string
    {
        $allowed = [
            UnscoredScoring::getUrl(),
            Scoring::getUrl(),
        ];

        if ($url && in_array($url, $allowed, true)) {
            return $url;
        }

        return UnscoredScoring::getUrl();
    }
}
