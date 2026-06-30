<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index'])->name('welcome');
Route::get('/report/revision-table/{examregistration}', [App\Http\Controllers\ReportController::class, 'createRevisionTablePDF'])->name('report.revision-table');
Route::get('/report/revision-sign/{examregistration}', [App\Http\Controllers\ReportController::class, 'createRevisionSignPDF'])->name('report.revision-sign');

Route::middleware('auth')->group(function () {
    Route::get('/report/examination/{examregistration}', [App\Http\Controllers\ReportController::class, 'createExamByChiefPDF'])->name('report.exam-chief');
    Route::get('/report/examination/thesis/{examregistration}/result', [App\Http\Controllers\ReportController::class, 'createThesisExamByChiefPDF'])->name('report.thesis-exam-chief');
    Route::get('/report/examination/thesis/{examregistration}/grading', [App\Http\Controllers\ReportController::class, 'createThesisExamByLecturePDF'])->name('report.thesis-exam-by-lecture');
    Route::get('/report/examination/thesis/{examregistration}/revision', [App\Http\Controllers\ReportController::class, 'createThesisRevisionByLecturePDF'])->name('report.thesis-rev-by-lecture');
    Route::get('/mypassword/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'showChangePasswordGet'])->name('mypassword.change');
    Route::post('/mypassword/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'changePasswordPost'])->name('mypassword.update');
    Route::post('/mypassword/reset/{id}', [App\Http\Controllers\Auth\PasswordChangeController::class, 'resetPasswordPost'])->name('mypassword.reset');
    Route::resource('profiles', App\Http\Controllers\ProfileController::class)->only(['index','edit','update']);
    Route::middleware('can:active')->group(function () {
        Route::put('/setting/selectionguidegroups/{guidegroup}/activation', [App\Http\Controllers\Setting\Selection\GuideGroupController::class, 'activation'])->name('selectionguidegroups.activation');
        Route::get('/setting/registrations/{student_id}/create', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class,'createByStudent'])->name('registrations.student');
        Route::get('/setting/registrations/{student_id}/show', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class,'showByStudent'])->name('registrations.show.student');
        Route::put('/setting/examregistrations/{examregistration}/scoreset', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'scoreSet'])->name('examregistrations.scoreset');
        Route::put('/setting/examregistrations/{examregistration}/mark-sent', [App\Http\Controllers\Setting\Examination\ExamScoreController::class, 'markSent'])->name('examregistrations.examscores.mark-sent');
        Route::post('/setting/examregistrations/paste-import',[App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'pasteImport'])->name('examregistrations.paste-import');
        Route::post('/setting/examregistrations/paste-import-check-duplicates', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'pasteImportCheckDuplicates'])->name('examregistrations.paste-import-check-duplicates');
        Route::post('/setting/examregistrations/paste-bulk-edit-resolve', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'pasteBulkEditResolve'])->name('examregistrations.paste-bulk-edit-resolve');
        Route::post('/setting/examregistrations/paste-bulk-edit', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'pasteBulkEdit'])->name('examregistrations.paste-bulk-edit');
        Route::get('/setting/examregistrations/{examregistration}/whatsapp/invite', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'whatsappInvite'])->name('examregistrations.whatsapp-invite');
        Route::get('/setting/examregistrations/{examregistration}/whatsapp/ralat', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'whatsappRalat'])->name('examregistrations.whatsapp-ralat');
        Route::resource('setting/navigations', App\Http\Controllers\Setting\NavigationController::class)->except('show');
        Route::resource('setting/selectionelementcomments', App\Http\Controllers\Setting\Selection\ElementCommentController::class)->except('show');
        Route::resource('setting/selectionguidegroups', App\Http\Controllers\Setting\Selection\GuideGroupController::class)->except('show');
        Route::resource('setting/selectionguides', App\Http\Controllers\Setting\Selection\GuideController::class)->except('show');
        Route::resource('setting/nuir-settings', App\Http\Controllers\Setting\Nuir\SettingController::class)
            ->except('show')
            ->middleware('can:manage nuir settings');
        Route::put('setting/nuir-settings/{nuirSetting}/toggle', [App\Http\Controllers\Setting\Nuir\SettingController::class, 'toggle'])
            ->name('nuir-settings.toggle')
            ->middleware('can:manage nuir settings');
        Route::middleware('can:access nuir/submission')->group(function () {
            Route::get('nuir/submission', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'index'])
                ->name('nuir.submission.index');
            Route::get('nuir/submission/create', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'create'])
                ->name('nuir.submission.create')
                ->middleware('can:create nuir submission');
            Route::post('nuir/submission', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'store'])
                ->name('nuir.submission.store')
                ->middleware('can:create nuir submission');
            Route::get('nuir/submission/{nuirSubmission}/edit', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'edit'])
                ->name('nuir.submission.edit')
                ->middleware('can:update nuir submission');
            Route::put('nuir/submission/{nuirSubmission}', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'update'])
                ->name('nuir.submission.update')
                ->middleware('can:update nuir submission');
            Route::put('nuir/submission/{nuirSubmission}/submit', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'submit'])
                ->name('nuir.submission.submit')
                ->middleware('can:update nuir submission');
            Route::get('nuir/submission/{nuirSubmission}/revise', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'createRevision'])
                ->name('nuir.submission.revise')
                ->middleware('can:update nuir submission');
            Route::post('nuir/submission/{nuirSubmission}/revise', [App\Http\Controllers\Selection\NuirSubmissionController::class, 'storeRevision'])
                ->name('nuir.submission.store-revision')
                ->middleware('can:update nuir submission');
        });
        Route::middleware('can:review nuir submission')->group(function () {
            Route::get('setting/nuir/submissions', [App\Http\Controllers\Setting\Nuir\SubmissionController::class, 'index'])
                ->name('nuir.review.index');
            Route::get('setting/nuir/submissions/{nuirSubmission}', [App\Http\Controllers\Setting\Nuir\SubmissionController::class, 'show'])
                ->name('nuir.review.show');
            Route::patch('setting/nuir/references/{nuirReference}', [App\Http\Controllers\Setting\Nuir\SubmissionController::class, 'reviewReference'])
                ->name('nuir.review.reference');
            Route::put('setting/nuir/submissions/{nuirSubmission}/review', [App\Http\Controllers\Setting\Nuir\SubmissionController::class, 'review'])
                ->name('nuir.review.submit');
            Route::get('setting/nuir/proposals', [App\Http\Controllers\Setting\Nuir\SubmissionController::class, 'proposals'])
                ->name('nuir.proposals.index');
            Route::put('setting/nuir/proposals/{nuirProposal}/finalize', [App\Http\Controllers\Setting\Nuir\SubmissionController::class, 'forceFinalize'])
                ->name('nuir.proposals.finalize');
        });
        Route::middleware(['can:read nuir proposal'])->group(function () {
            Route::get('nuir/proposal', [App\Http\Controllers\Selection\NuirProposalController::class, 'index'])
                ->name('nuir.proposal.index');
            Route::get('nuir/proposal/create', [App\Http\Controllers\Selection\NuirProposalController::class, 'create'])
                ->name('nuir.proposal.create')
                ->middleware('can:create nuir proposal');
            Route::post('nuir/proposal', [App\Http\Controllers\Selection\NuirProposalController::class, 'store'])
                ->name('nuir.proposal.store')
                ->middleware('can:create nuir proposal');
        });
        Route::middleware(['can:read nuir proposal'])->group(function () {
            Route::get('nuir/dosen', [App\Http\Controllers\Dosen\NuirProposalController::class, 'index'])
                ->name('nuir.dosen.index');
            Route::get('nuir/dosen/{nuirProposal}', [App\Http\Controllers\Dosen\NuirProposalController::class, 'show'])
                ->name('nuir.dosen.show');
            Route::put('nuir/dosen/{nuirProposal}/accept', [App\Http\Controllers\Dosen\NuirProposalController::class, 'accept'])
                ->name('nuir.dosen.accept')
                ->middleware('can:respond nuir proposal');
            Route::put('nuir/dosen/{nuirProposal}/reject', [App\Http\Controllers\Dosen\NuirProposalController::class, 'reject'])
                ->name('nuir.dosen.reject')
                ->middleware('can:respond nuir proposal');
            Route::patch('nuir/dosen/{nuirProposal}/references/{nuirReference}', [App\Http\Controllers\Dosen\NuirProposalController::class, 'reviewReference'])
                ->name('nuir.dosen.review-reference')
                ->middleware('can:respond nuir proposal');
            Route::patch('nuir/dosen/{nuirProposal}/content', [App\Http\Controllers\Dosen\NuirProposalController::class, 'reviewContent'])
                ->name('nuir.dosen.review-content')
                ->middleware('can:respond nuir proposal');
        });
        Route::resource('selection/stages', App\Http\Controllers\Selection\StageController::class)->only('store');
        // doshboard mahasiswa
        Route::get('selection/guides/{stage}', [App\Http\Controllers\Selection\GuideController::class,'index'])->name('guides.index');
        Route::put('selection/guides/{guide}/cancel', [App\Http\Controllers\Selection\GuideController::class,'cancel'])->name('guides.cancel');
        Route::get('examination/student', [App\Http\Controllers\Examination\StudentController::class,'index'])->name('exam.student.index');
        Route::get('examination/student/{student}/get-revision', [App\Http\Controllers\Examination\StudentController::class,'getRevision'])->name('exam.student.get-revision');
        // dashboard dosen (scoring edit/update dipertahankan karena Filament masih menggunakannya)
        Route::get('examination/scoring/{scoring}/edit', [App\Http\Controllers\Examination\ScoreController::class,'edit'])->name('scoring.edit');
        Route::put('examination/scoring/{scoring}', [App\Http\Controllers\Examination\ScoreController::class,'update'])->name('scoring.update');
        Route::get('selection/respon', [App\Http\Controllers\Selection\GuideResponController::class,'index'])->name('respons.index');
        Route::get('selection/respon/result', [App\Http\Controllers\Selection\GuideResponController::class,'result'])->name('respons.result');
        Route::put('selection/respons/{guide}/accept', [App\Http\Controllers\Selection\GuideResponController::class,'accept'])->name('respons.accept');
        Route::put('selection/respons/{guide}/decline', [App\Http\Controllers\Selection\GuideResponController::class,'decline'])->name('respons.decline');
        Route::put('selection/respons/{guide}/retract', [App\Http\Controllers\Selection\GuideResponController::class,'retract'])->name('respons.retract');
        Route::resource('selection/guides', App\Http\Controllers\Selection\GuideController::class)->except('show','index');
        Route::redirect('information/guides', '/home/information/guides')->name('information.guide');
        Route::redirect('information/pass', '/home/information/pass')->name('information.pass');
    });
    Route::get('datatable/{id}', function(App\DataTables\ExamRegistrationsDataTable $dataTable, $id){
        return $dataTable->with('id', $id)
        ->render('layouts.setting');
    });
});

Route::get('information/recap-list/{generation}/{context}', [App\Http\Controllers\Information\GuideInformationController::class,'recap'])->name('information.recap');
Route::get('hasil-ujian', [App\Http\Controllers\Examination\StudentController::class,'setRevisionDate'])->name('exam.result');
Route::post('print-hasil', [App\Http\Controllers\Examination\StudentController::class,'showRevisionByDate'])->name('set.exam.date');

Auth::routes();

Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');

Route::get('/go-home', function () {
    $user = auth()->user();

    if ($user->hasRole('admin')) {
        return redirect('/admin');
    }

    if ($user->hasRole('dosen')) {
        return redirect('/home');
    }

    if ($user->hasRole('dbs')) {
        return redirect('/dbs');
    }

    if ($user->hasRole('mahasiswa')) {
        return redirect('/mahasiswa');
    }

    return redirect()->route('dashboard');
})->middleware('auth')->name('home');

Route::impersonate();
