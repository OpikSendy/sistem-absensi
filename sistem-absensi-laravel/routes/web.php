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

// ─── Global Notifications ──────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/notifications/unread', [\App\Http\Controllers\NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
});
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

        // Tugas Karyawan
        Route::get('/tugas', [UserController::class, 'tugas'])->name('tugas');
        Route::put('/tugas/{id}', [UserController::class, 'updateTugasStatus'])->name('tugas.update');

        // Absensi actions (AJAX/JSON)
        Route::post('/absensi/store', [AbsensiController::class, 'store'])->name('absensi.store');
        Route::post('/absensi/izin', [AbsensiController::class, 'storeIzin'])->name('absensi.izin');

        // Analytics
        Route::get('/analytics/ranking', [UserController::class, 'getRankingData'])->name('analytics.ranking');
        Route::get('/analytics/my-discipline', [UserController::class, 'getMyDisciplineData'])->name('analytics.my_discipline');
        Route::get('/analytics/my-distribution', [UserController::class, 'getMyDistributionData'])->name('analytics.my_distribution');
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

        // Penempatan Shift Karyawan
        Route::get('/user-shifts', [AdminController::class, 'userShifts'])->name('user_shifts');
        Route::post('/user-shifts/{user}', [AdminController::class, 'updateUserShift'])->name('user_shifts.update');


        Route::get('/tugas', [AdminController::class, 'tugas'])->name('tugas');
        Route::post('/tugas', [AdminController::class, 'storeTugas'])->name('tugas.store');
        Route::put('/tugas/{tugas}', [AdminController::class, 'updateTugas'])->name('tugas.update');
        Route::delete('/tugas/{tugas}', [AdminController::class, 'destroyTugas'])->name('tugas.destroy');

        // Penugasan Karyawan
        Route::get('/penugasan', [App\Http\Controllers\PenugasanController::class, 'index'])->name('penugasan');
        Route::post('/penugasan', [App\Http\Controllers\PenugasanController::class, 'store'])->name('penugasan.store');
        Route::put('/penugasan/{id}', [App\Http\Controllers\PenugasanController::class, 'update'])->name('penugasan.update');
        Route::delete('/penugasan/{id}', [App\Http\Controllers\PenugasanController::class, 'destroy'])->name('penugasan.destroy');

        // Absensi management (AJAX/JSON)
        Route::post('/absensi/status', [AbsensiController::class, 'updateStatus'])->name('absensi.status');
        Route::get('/absensi/{absensi}', [AbsensiController::class, 'detail'])->name('absensi.detail');

        // Admin Profile
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
        Route::post('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');

        // Export
        Route::get('/export/excel', [ExportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ExportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/rekap/excel', [ExportController::class, 'exportRekapExcel'])->name('export.rekap.excel');
        Route::get('/export/rekap/pdf', [ExportController::class, 'exportRekapPdf'])->name('export.rekap.pdf');

        // Analytics
        Route::get('/analytics/ranking', [AdminController::class, 'getRankingData'])->name('analytics.ranking');
        Route::get('/analytics/user-discipline', [AdminController::class, 'getUserDisciplineData'])->name('analytics.user_discipline');
        Route::get('/analytics/user-distribution', [AdminController::class, 'getUserDistributionData'])->name('analytics.user_distribution');
    });

