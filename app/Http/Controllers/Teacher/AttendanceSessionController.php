<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AttendanceSessionStoreRequest;
use App\Models\AttendanceSession;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\AttendanceException;
use App\Services\AttendanceSessionService;
use App\Services\AttendanceTokenService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSessionController extends Controller
{
    public function __construct(
        private readonly AttendanceSessionService $sessionService,
        private readonly AttendanceTokenService $tokenService
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isTeacher(), 403);

        $query = AttendanceSession::query()
            ->with(['schoolClass:id,name', 'subject:id,name'])
            ->where('school_id', $user->school_id)
            ->orderByDesc('started_at');

        if ($user->role !== 'school_admin') {
            $query->where('teacher_id', $user->id);
        }

        $sessions = $query->limit(100)->get()->map(function (AttendanceSession $session) {
            return [
                'id' => $session->id,
                'status' => $session->status,
                'started_at' => optional($session->started_at)?->toIso8601String(),
                'started_at_label' => optional($session->started_at)?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'school_class' => $session->schoolClass ? ['id' => $session->schoolClass->id, 'name' => $session->schoolClass->name] : null,
                'subject' => $session->subject ? ['id' => $session->subject->id, 'name' => $session->subject->name] : null,
            ];
        })->values();

        return Inertia::render('Attendance/TeacherSessionsIndex', [
            'sessions' => $sessions,
            'classes' => SchoolClass::query()
                ->where('school_id', $user->school_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'subjects' => Subject::query()
                ->where('school_id', $user->school_id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, AttendanceSession $session): Response
    {
        $session = $this->findSessionForTeacher($request, $session->id)->load(['schoolClass', 'subject']);

        return Inertia::render('Attendance/TeacherSessionShow', [
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'qr_rotate_seconds' => $session->qr_rotate_seconds,
                'school_class' => $session->schoolClass ? ['id' => $session->schoolClass->id, 'name' => $session->schoolClass->name] : null,
                'subject' => $session->subject ? ['id' => $session->subject->id, 'name' => $session->subject->name] : null,
            ],
        ]);
    }

    public function store(AttendanceSessionStoreRequest $request): JsonResponse|RedirectResponse
    {
        $teacher = $request->user();
        $payload = $request->validated();
        $qrDynamic = $request->has('qr_dynamic') ? $request->boolean('qr_dynamic') : true;
        $locationValidation = $request->has('location_validation') ? $request->boolean('location_validation') : false;

        $class = SchoolClass::query()
            ->where('id', $payload['class_id'])
            ->where('school_id', $teacher->school_id)
            ->first();

        $subject = Subject::query()
            ->where('id', $payload['subject_id'])
            ->where('school_id', $teacher->school_id)
            ->first();

        if (!$class || !$subject) {
            if (!$request->expectsJson()) {
                return back()
                    ->withInput()
                    ->withErrors(['class_id' => 'Kelas/mapel tidak ditemukan di sekolah Anda.']);
            }

            return response()->json([
                'message' => 'Kelas/mapel tidak ditemukan di sekolah Anda.',
            ], 422);
        }

        $session = AttendanceSession::create([
            'school_id' => $teacher->school_id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'started_at' => CarbonImmutable::parse($payload['started_at']),
            'ended_at' => CarbonImmutable::parse($payload['ended_at']),
            'late_tolerance_minutes' => $payload['late_tolerance_minutes'] ?? 0,
            'qr_dynamic' => $qrDynamic,
            'qr_rotate_seconds' => $payload['qr_rotate_seconds'] ?? 30,
            'location_validation' => $locationValidation,
            'center_lat' => $locationValidation ? ($payload['center_lat'] ?? null) : null,
            'center_lng' => $locationValidation ? ($payload['center_lng'] ?? null) : null,
            'radius_meters' => $locationValidation ? ($payload['radius_meters'] ?? null) : null,
            'session_secret' => bin2hex(random_bytes(32)),
            'status' => 'draft',
        ]);

        if (!$request->expectsJson()) {
            return redirect()
                ->route('teacher.attendance.sessions.show', $session)
                ->with('success', 'Sesi absensi berhasil dibuat.');
        }

        return response()->json([
            'message' => 'Sesi absensi berhasil dibuat.',
            'data' => [
                'id' => $session->id,
                'status' => $session->status,
            ],
        ], 201);
    }

    public function open(Request $request, AttendanceSession $session): JsonResponse
    {
        try {
            $session = $this->findSessionForTeacher($request, $session->id);
            $opened = $this->sessionService->openSession($session, $request->user());

            return response()->json([
                'message' => 'Sesi dibuka.',
                'data' => [
                    'id' => $opened->id,
                    'status' => $opened->status,
                    'opened_at' => optional($opened->opened_at)->toDateTimeString(),
                ],
            ]);
        } catch (AttendanceException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function currentQr(Request $request, AttendanceSession $session): JsonResponse
    {
        $session = $this->findSessionForTeacher($request, $session->id);

        if ($session->status !== 'open') {
            return response()->json([
                'message' => 'Sesi belum dibuka.',
            ], 422);
        }

        $tokenData = $this->tokenService->generateForSession($session);
        $scanUrl = route('attendance.scan', [
            'sid' => $session->id,
            't' => $tokenData['token'],
        ]);

        return response()->json([
            'data' => [
                'session_id' => $session->id,
                'token' => $tokenData['token'],
                'slot' => $tokenData['slot'],
                'expires_at' => $tokenData['expires_at']->toIso8601String(),
                'scan_url' => $scanUrl,
            ],
        ]);
    }

    public function realtime(Request $request, AttendanceSession $session): JsonResponse
    {
        $session = $this->findSessionForTeacher($request, $session->id);

        $records = $session->records()
            ->with('student:id,name')
            ->orderByDesc('scanned_at')
            ->limit(100)
            ->get();

        $summary = [
            'hadir' => $records->where('status', 'hadir')->count(),
            'terlambat' => $records->where('status', 'terlambat')->count(),
            'alpha' => $records->where('status', 'alpha')->count(),
            'total' => $records->count(),
        ];

        return response()->json([
            'data' => [
                'summary' => $summary,
                'records' => $records->map(function ($record) {
                    return [
                        'student' => $record->student?->name,
                        'status' => $record->status,
                        'scanned_at' => optional($record->scanned_at)->toDateTimeString(),
                        'late_minutes' => $record->late_minutes,
                        'distance_meters' => $record->distance_meters,
                    ];
                }),
            ],
        ]);
    }

    public function close(Request $request, AttendanceSession $session): JsonResponse
    {
        try {
            $session = $this->findSessionForTeacher($request, $session->id);
            $closed = $this->sessionService->closeSessionAndMarkAlpha($session, $request->user());

            return response()->json([
                'message' => 'Sesi ditutup dan alpha otomatis ditandai.',
                'data' => [
                    'id' => $closed->id,
                    'status' => $closed->status,
                    'closed_at' => optional($closed->closed_at)->toDateTimeString(),
                ],
            ]);
        } catch (AttendanceException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function findSessionForTeacher(Request $request, int $id): AttendanceSession
    {
        $user = $request->user();
        abort_unless($user?->isTeacher(), 403);

        $query = AttendanceSession::query()
            ->where('id', $id)
            ->where('school_id', $user->school_id);

        if ($user->role !== 'school_admin') {
            $query->where('teacher_id', $user->id);
        }

        return $query->firstOrFail();
    }
}
