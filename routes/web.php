<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\SchoolDashboardController;
use App\Http\Controllers\Admin\SchoolReportController;
use App\Http\Controllers\Admin\SchoolSettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AttendanceExportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Discipline\LateHistoryController;
use App\Http\Controllers\Discipline\LeaveRequestController;
use App\Http\Controllers\Discipline\ViolationController;
use App\Http\Controllers\Student\StudentIdController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\Teacher\AttendanceSessionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    if (auth()->user()?->isStudent()) {
        return redirect()->route('student.home');
    }

    if (auth()->user()?->isSchoolAdmin()) {
        return redirect()->route('admin.dashboard');
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

    Route::prefix('student')->name('student.')->group(function () {
        Route::get('/id', [StudentIdController::class, 'show'])->name('id.show');
        Route::get('/id/verify', [StudentIdController::class, 'verify'])->name('id.verify');
        Route::get('/leave-requests', [LeaveRequestController::class, 'studentIndex'])->name('leave.index');
        Route::post('/leave-requests', [LeaveRequestController::class, 'studentStore'])->name('leave.store');
    });

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

    Route::prefix('discipline')->name('discipline.')->group(function () {
        Route::get('/leave-requests', [LeaveRequestController::class, 'staffIndex'])->name('leave.staff.index');
        Route::post('/leave-requests/{leaveRequest}/decision', [LeaveRequestController::class, 'decide'])->name('leave.staff.decide');
        Route::get('/violations', [ViolationController::class, 'index'])->name('violations.index');
        Route::post('/violations', [ViolationController::class, 'store'])->name('violations.store');
        Route::get('/late-history', [LateHistoryController::class, 'index'])->name('late-history.index');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [SchoolDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports/daily', [SchoolReportController::class, 'daily'])->name('reports.daily');
        Route::get('/reports/daily/export', [SchoolReportController::class, 'exportDaily'])->name('reports.daily.export');
        Route::get('/settings/branding', [SchoolSettingsController::class, 'branding'])->name('settings.branding');
        Route::post('/settings/branding', [SchoolSettingsController::class, 'updateBranding'])->name('settings.branding.update');
        Route::get('/settings/users', [UserManagementController::class, 'index'])->name('settings.users');
        Route::post('/settings/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('settings.users.role');
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
    });
});
