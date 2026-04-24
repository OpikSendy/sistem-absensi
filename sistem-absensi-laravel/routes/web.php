<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\ExportController;

// ─── Root redirect ───────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── User Area ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:user'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
        Route::get('/absensi', [UserController::class, 'absensi'])->name('absensi');
        Route::get('/profile', [UserController::class, 'profile'])->name('profile');
        Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
        Route::post('/avatar/upload', [UserController::class, 'uploadAvatar'])->name('avatar.upload');
        Route::delete('/avatar', [UserController::class, 'deleteAvatar'])->name('avatar.delete');

        // Absensi actions (AJAX/JSON)
        Route::post('/absensi/store', [AbsensiController::class, 'store'])->name('absensi.store');
    });

// ─── Admin Area ──────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

        Route::get('/shifts', [AdminController::class, 'shifts'])->name('shifts');
        Route::post('/shifts', [AdminController::class, 'storeShift'])->name('shifts.store');
        Route::put('/shifts/{shift}', [AdminController::class, 'updateShift'])->name('shifts.update');
        Route::delete('/shifts/{shift}', [AdminController::class, 'destroyShift'])->name('shifts.destroy');

        Route::get('/tugas', [AdminController::class, 'tugas'])->name('tugas');

        // Absensi management (AJAX/JSON)
        Route::post('/absensi/status', [AbsensiController::class, 'updateStatus'])->name('absensi.status');
        Route::get('/absensi/{absensi}', [AbsensiController::class, 'detail'])->name('absensi.detail');

        // Export
        Route::get('/export/excel', [ExportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ExportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/rekap/excel', [ExportController::class, 'exportRekapExcel'])->name('export.rekap.excel');
        Route::get('/export/rekap/pdf', [ExportController::class, 'exportRekapPdf'])->name('export.rekap.pdf');
    });

