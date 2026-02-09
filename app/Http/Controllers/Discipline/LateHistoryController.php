<?php

namespace App\Http\Controllers\Discipline;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LateHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor?->canManageDiscipline(), 403);

        $from = (string) $request->query('from', CarbonImmutable::now()->subDays(7)->toDateString());
        $to = (string) $request->query('to', CarbonImmutable::now()->toDateString());
        $classId = $request->query('class_id');

        $query = AttendanceRecord::query()
            ->with(['student:id,name', 'session:id,class_id,started_at', 'session.schoolClass:id,name'])
            ->where('school_id', $actor->school_id)
            ->where('status', 'terlambat')
            ->whereDate('scanned_at', '>=', $from)
            ->whereDate('scanned_at', '<=', $to)
            ->orderByDesc('scanned_at');

        if ($classId) {
            $query->whereHas('session', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        $rows = $query->limit(200)->get()->map(function (AttendanceRecord $record) {
            return [
                'id' => $record->id,
                'student_name' => $record->student?->name,
                'class_name' => $record->session?->schoolClass?->name,
                'late_minutes' => $record->late_minutes,
                'scanned_at' => optional($record->scanned_at)->format('Y-m-d H:i'),
                'session_started_at' => optional($record->session?->started_at)?->format('Y-m-d H:i'),
            ];
        })->values();

        $classes = SchoolClass::query()
            ->where('school_id', $actor->school_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Discipline/LateHistory', [
            'rows' => $rows,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'class_id' => $classId ? (int) $classId : null,
            ],
            'classes' => $classes,
        ]);
    }
}
