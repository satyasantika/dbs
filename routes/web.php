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

Route::get('/', function () {
    return to_route('home');
    // return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/report/revision-table/{examregistration}', [App\Http\Controllers\ReportController::class, 'createRevisionTablePDF'])->name('report.revision-table');
    Route::get('/report/revision-sign/{examregistration}', [App\Http\Controllers\ReportController::class, 'createRevisionSignPDF'])->name('report.revision-sign');
    Route::get('/report/examination/{examregistration}', [App\Http\Controllers\ReportController::class, 'createExamByChiefPDF'])->name('report.exam-chief');
    Route::get('/mypassword/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'showChangePasswordGet'])->name('mypassword.change');
    Route::post('/mypassword/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'changePasswordPost'])->name('mypassword.update');
    Route::post('/mypassword/reset/{id}', [App\Http\Controllers\Auth\PasswordChangeController::class, 'resetPasswordPost'])->name('mypassword.reset');
    Route::resource('profiles', App\Http\Controllers\ProfileController::class)->only(['index','edit','update']);
    Route::middleware('can:active')->group(function () {
        Route::put('/setting/users/{user}/activation', [App\Http\Controllers\Setting\UserController::class, 'activation'])->name('users.activation');
        Route::put('/setting/selectionguideallocations/{guideallocation}/activation', [App\Http\Controllers\Setting\Selection\GuideAllocationController::class, 'activation'])->name('selectionguideallocations.activation');
        Route::put('/setting/selectionguidegroups/{guidegroup}/activation', [App\Http\Controllers\Setting\Selection\GuideGroupController::class, 'activation'])->name('selectionguidegroups.activation');
        Route::put('/setting/examregistrations/{examregistration}/scoreset', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'scoreSet'])->name('examregistrations.scoreset');
        Route::get('/setting/examregistrations/date/{id}', [App\Http\Controllers\Setting\Examination\ExamRegistrationController::class, 'index2'])->name('examregistrations.date');
        Route::get('/admin/scoringyets', [App\Http\Controllers\Examination\AdminController::class, 'getExaminerScoringYet'])->name('get.examinerscoringyet');
        Route::resource('setting/roles', App\Http\Controllers\Setting\RoleController::class)->except('show');
        Route::resource('setting/permissions', App\Http\Controllers\Setting\PermissionController::class)->except('show');
        Route::resource('setting/rolepermissions', App\Http\Controllers\Setting\RolePermissionController::class)->only('edit', 'update');
        Route::resource('setting/userpermissions', App\Http\Controllers\Setting\UserPermissionController::class)->only('edit', 'update');
        Route::resource('setting/userroles', App\Http\Controllers\Setting\UserRoleController::class)->only('edit', 'update');
        Route::resource('setting/users', App\Http\Controllers\Setting\UserController::class)->except('show');
        Route::resource('setting/navigations', App\Http\Controllers\Setting\NavigationController::class)->except('show');
        Route::resource('setting/selectionstages', App\Http\Controllers\Setting\Selection\StageController::class)->except('show');
        Route::resource('setting/selectionelements', App\Http\Controllers\Setting\Selection\ElementController::class)->except('show');
        Route::resource('setting/selectionelementcomments', App\Http\Controllers\Setting\Selection\ElementCommentController::class)->except('show');
        Route::resource('setting/selectionguideallocations', App\Http\Controllers\Setting\Selection\GuideAllocationController::class)->except('show');
        Route::resource('setting/selectionguidegroups', App\Http\Controllers\Setting\Selection\GuideGroupController::class)->except('show');
        Route::resource('setting/selectionguides', App\Http\Controllers\Setting\Selection\GuideController::class)->except('show');
        Route::resource('setting/guideexaminers', App\Http\Controllers\Setting\Examination\GuideExaminerController::class)->except('show');
        Route::resource('setting/examregistrations', App\Http\Controllers\Setting\Examination\ExamRegistrationController::class)->except('show');
        Route::resource('setting/examregistrations.examscores', App\Http\Controllers\Setting\Examination\ExamScoreController::class)->only('index','edit','update');
        Route::resource('selection/stages', App\Http\Controllers\Selection\StageController::class)->only('store');
        // doshboard mahasiswa
        Route::get('selection/guides/{stage}', [App\Http\Controllers\Selection\GuideController::class,'index'])->name('guides.index');
        Route::put('selection/guides/{guide}/cancel', [App\Http\Controllers\Selection\GuideController::class,'cancel'])->name('guides.cancel');
        Route::get('examination/student', [App\Http\Controllers\Examination\StudentController::class,'index'])->name('exam.student.index');
        Route::get('examination/student/{student}/get-revision', [App\Http\Controllers\Examination\StudentController::class,'getRevision'])->name('exam.student.get-revision');
        // dashboard ketua penguji
        Route::get('examination/chief', [App\Http\Controllers\Examination\ChiefController::class,'index'])->name('chief.index');
        Route::get('examination/chief/{chief}', [App\Http\Controllers\Examination\ChiefController::class,'show'])->name('chief.show');
        Route::put('examination/chief/{chief}/pass', [App\Http\Controllers\Examination\ChiefController::class,'pass'])->name('chief.pass');
        // dashboard dosen
        Route::get('examination/scoring', [App\Http\Controllers\Examination\ScoreController::class,'index'])->name('scoring.index');
        Route::get('examination/scoring-archieves', [App\Http\Controllers\Examination\ScoreController::class,'archieves'])->name('scoring.archieves');
        Route::get('examination/scoring/{scoring}/edit', [App\Http\Controllers\Examination\ScoreController::class,'edit'])->name('scoring.edit');
        Route::put('examination/scoring/{scoring}', [App\Http\Controllers\Examination\ScoreController::class,'update'])->name('scoring.update');
        Route::get('selection/respon', [App\Http\Controllers\Selection\GuideResponController::class,'index'])->name('respons.index');
        Route::get('selection/respon/result', [App\Http\Controllers\Selection\GuideResponController::class,'result'])->name('respons.result');
        Route::put('selection/respons/{guide}/accept', [App\Http\Controllers\Selection\GuideResponController::class,'accept'])->name('respons.accept');
        Route::put('selection/respons/{guide}/decline', [App\Http\Controllers\Selection\GuideResponController::class,'decline'])->name('respons.decline');
        Route::put('selection/respons/{guide}/retract', [App\Http\Controllers\Selection\GuideResponController::class,'retract'])->name('respons.retract');
        Route::resource('selection/guides', App\Http\Controllers\Selection\GuideController::class)->except('show','index');
    });
    Route::get('datatable/{id}', function(App\DataTables\ExamRegistrationsDataTable $dataTable, $id){
    return $dataTable->with('id', $id)
            ->render('layouts.setting');
    });
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
