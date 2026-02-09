<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isSchoolAdmin(), 403);

        $today = CarbonImmutable::now()->startOfDay();
        $weekStart = $today->subDays(6);

        $totalStudents = (int) DB::table('class_students')
            ->where('school_id', $user->school_id)
            ->where('is_active', true)
            ->distinct('student_id')
            ->count('student_id');

        $presentToday = (int) DB::table('attendance_records as ar')
            ->join('attendance_sessions as s', 's.id', '=', 'ar.session_id')
            ->where('ar.school_id', $user->school_id)
            ->whereIn('ar.status', ['hadir', 'terlambat'])
            ->whereDate('s.started_at', $today->toDateString())
            ->distinct('ar.student_id')
            ->count('ar.student_id');

        $todayAttendanceRate = $totalStudents > 0
            ? round(($presentToday / $totalStudents) * 100, 1)
            : 0.0;

        $weekly = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->addDays($i);
            $counts = DB::table('attendance_records as ar')
                ->join('attendance_sessions as s', 's.id', '=', 'ar.session_id')
                ->where('ar.school_id', $user->school_id)
                ->whereDate('s.started_at', $date->toDateString())
                ->selectRaw("sum(case when ar.status = 'hadir' then 1 else 0 end) as hadir")
                ->selectRaw("sum(case when ar.status = 'terlambat' then 1 else 0 end) as terlambat")
                ->selectRaw("sum(case when ar.status = 'alpha' then 1 else 0 end) as alpha")
                ->first();

            $weekly[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('d M'),
                'hadir' => (int) ($counts->hadir ?? 0),
                'terlambat' => (int) ($counts->terlambat ?? 0),
                'alpha' => (int) ($counts->alpha ?? 0),
            ];
        }

        $disciplineRows = DB::table('attendance_records as ar')
            ->join('attendance_sessions as s', 's.id', '=', 'ar.session_id')
            ->join('school_classes as c', 'c.id', '=', 's.class_id')
            ->where('ar.school_id', $user->school_id)
            ->whereBetween('s.started_at', [$weekStart->startOfDay(), $today->endOfDay()])
            ->groupBy('c.id', 'c.name')
            ->select('c.id', 'c.name')
            ->selectRaw("sum(case when ar.status in ('hadir','terlambat') then 1 else 0 end) as present_count")
            ->selectRaw('count(*) as total_count')
            ->get();

        $topClasses = $disciplineRows
            ->map(function ($item) {
                $rate = ((int) $item->total_count) > 0
                    ? round(((int) $item->present_count / (int) $item->total_count) * 100, 1)
                    : 0;

                return [
                    'class_id' => (int) $item->id,
                    'class_name' => $item->name,
                    'present_count' => (int) $item->present_count,
                    'total_count' => (int) $item->total_count,
                    'rate' => $rate,
                ];
            })
            ->sortByDesc('rate')
            ->take(5)
            ->values();

        $pendingPermits = (int) DB::table('leave_requests')
            ->where('school_id', $user->school_id)
            ->where('status', 'pending')
            ->count();

        $violationPointsMonth = (int) DB::table('student_violations')
            ->where('school_id', $user->school_id)
            ->whereBetween('occurred_on', [$today->startOfMonth()->toDateString(), $today->toDateString()])
            ->sum('points');

        return Inertia::render('Admin/SchoolDashboard', [
            'summary' => [
                'total_students' => $totalStudents,
                'present_today' => $presentToday,
                'attendance_rate_today' => $todayAttendanceRate,
                'pending_permits' => $pendingPermits,
                'violation_points_month' => $violationPointsMonth,
            ],
            'weekly' => $weekly,
            'top_classes' => $topClasses,
        ]);
    }
}
