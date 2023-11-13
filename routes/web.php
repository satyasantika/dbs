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
    Route::get('/mypassword/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'showChangePasswordGet'])->name('mypassword.change');
    Route::post('/mypassword/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'changePasswordPost'])->name('mypassword.update');
    Route::post('/mypassword/reset/{id}', [App\Http\Controllers\Auth\PasswordChangeController::class, 'resetPasswordPost'])->name('mypassword.reset');
    Route::resource('profiles', App\Http\Controllers\ProfileController::class)->only(['index','edit','update']);
    Route::middleware('can:active')->group(function () {
        Route::put('/setting/users/{user}/activation', [App\Http\Controllers\Setting\UserController::class, 'activation'])->name('users.activation');
        Route::put('/setting/selectionguideallocations/{guideallocation}/activation', [App\Http\Controllers\Setting\Selection\GuideAllocationController::class, 'activation'])->name('selectionguideallocations.activation');
        Route::put('/setting/selectionguidegroups/{guidegroup}/activation', [App\Http\Controllers\Setting\Selection\GuideGroupController::class, 'activation'])->name('selectionguidegroups.activation');
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
        Route::resource('selection/stages', App\Http\Controllers\Selection\StageController::class)->only('store');
        // doshboard mahasiswa
        Route::get('selection/guides/{stage}', [App\Http\Controllers\Selection\GuideController::class,'index'])->name('guides.index');
        Route::put('selection/guides/{guide}/cancel', [App\Http\Controllers\Selection\GuideController::class,'cancel'])->name('guides.cancel');
        // dashboard dosen
        Route::get('selection/respon', [App\Http\Controllers\Selection\GuideResponController::class,'index'])->name('respons.index');
        Route::get('selection/respon/result', [App\Http\Controllers\Selection\GuideResponController::class,'result'])->name('respons.result');
        Route::put('selection/respons/{guide}/accept', [App\Http\Controllers\Selection\GuideResponController::class,'accept'])->name('respons.accept');
        Route::put('selection/respons/{guide}/decline', [App\Http\Controllers\Selection\GuideResponController::class,'decline'])->name('respons.decline');
        Route::put('selection/respons/{guide}/retract', [App\Http\Controllers\Selection\GuideResponController::class,'retract'])->name('respons.retract');
        Route::resource('selection/guides', App\Http\Controllers\Selection\GuideController::class)->except('show','index');
    });
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
