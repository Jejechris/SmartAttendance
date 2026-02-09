<?php

namespace App\Http\Controllers\Discipline;

use App\Http\Controllers\Controller;
use App\Models\StudentViolation;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ViolationController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor?->canManageDiscipline(), 403);

        $rows = StudentViolation::query()
            ->with(['student:id,name', 'creator:id,name'])
            ->where('school_id', $actor->school_id)
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->limit(150)
            ->get()
            ->map(function (StudentViolation $item) {
                return [
                    'id' => $item->id,
                    'student_name' => $item->student?->name,
                    'points' => $item->points,
                    'category' => $item->category,
                    'notes' => $item->notes,
                    'occurred_on' => optional($item->occurred_on)->format('Y-m-d'),
                    'creator_name' => $item->creator?->name,
                ];
            })
            ->values();

        $students = User::query()
            ->where('school_id', $actor->school_id)
            ->where('role', 'student')
            ->orderBy('name')
            ->get(['id', 'name']);

        $pointLeaderboard = StudentViolation::query()
            ->select('student_id')
            ->selectRaw('sum(points) as total_points')
            ->where('school_id', $actor->school_id)
            ->groupBy('student_id')
            ->orderByDesc('total_points')
            ->limit(5)
            ->with('student:id,name')
            ->get()
            ->map(function (StudentViolation $item) {
                return [
                    'student_name' => $item->student?->name,
                    'total_points' => (int) $item->total_points,
                ];
            })
            ->values();

        return Inertia::render('Discipline/Violations', [
            'rows' => $rows,
            'students' => $students,
            'leaderboard' => $pointLeaderboard,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor?->canManageDiscipline(), 403);

        $payload = $request->validate([
            'student_id' => ['required', 'integer'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'category' => ['required', 'string', 'min:3', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'occurred_on' => ['required', 'date', 'before_or_equal:'.CarbonImmutable::now()->toDateString()],
        ]);

        $student = User::query()
            ->where('id', $payload['student_id'])
            ->where('school_id', $actor->school_id)
            ->where('role', 'student')
            ->first();

        if (! $student) {
            return back()->with('error', 'Siswa tidak valid untuk sekolah Anda.');
        }

        $violation = StudentViolation::create([
            'school_id' => $actor->school_id,
            'student_id' => $student->id,
            'points' => $payload['points'],
            'category' => $payload['category'],
            'notes' => $payload['notes'] ?? null,
            'occurred_on' => $payload['occurred_on'],
            'created_by' => $actor->id,
        ]);

        $this->activityLogService->log(
            schoolId: (int) $actor->school_id,
            actor: $actor,
            action: 'violation.created',
            targetType: 'student_violation',
            targetId: (int) $violation->id,
            meta: ['student_id' => $student->id, 'points' => $violation->points],
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return back()->with('success', 'Poin pelanggaran berhasil dicatat.');
    }
}
