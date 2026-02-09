<?php

use App\Http\Controllers\AttendanceExportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\Teacher\AttendanceSessionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    if (auth()->user()?->isStudent()) {
        return redirect()->route('student.home');
    }

    return redirect()->route('teacher.attendance.sessions.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/student/home', function () {
        abort_unless(auth()->user()?->isStudent(), 403);
        return Inertia::render('Attendance/StudentHome');
    })->name('student.home');

    Route::get('/teacher/attendance/sessions', [AttendanceSessionController::class, 'index'])
        ->name('teacher.attendance.sessions.index');

    Route::prefix('teacher/attendance')->name('teacher.attendance.')->group(function () {
        Route::post('/sessions', [AttendanceSessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{session}', [AttendanceSessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/open', [AttendanceSessionController::class, 'open'])->name('sessions.open');
        Route::post('/sessions/{session}/close', [AttendanceSessionController::class, 'close'])->name('sessions.close');
        Route::get('/sessions/{session}/qr', [AttendanceSessionController::class, 'currentQr'])
            ->middleware('throttle:60,1')
            ->name('sessions.qr');
        Route::get('/sessions/{session}/realtime', [AttendanceSessionController::class, 'realtime'])
            ->middleware('throttle:60,1')
            ->name('sessions.realtime');
        Route::get('/sessions/{session}/export', [AttendanceExportController::class, 'export'])->name('sessions.export');
    });

    Route::get('/attendance/scan', [StudentAttendanceController::class, 'scanPage'])
        ->middleware('throttle:60,1')
        ->name('attendance.scan');
    Route::post('/attendance/check-in', [StudentAttendanceController::class, 'checkIn'])
        ->middleware('throttle:20,1')
        ->name('attendance.check-in');
});
