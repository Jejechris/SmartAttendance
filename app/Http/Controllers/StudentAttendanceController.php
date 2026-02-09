<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCheckInRequest;
use App\Models\AttendanceSession;
use App\Services\AttendanceCheckInService;
use App\Services\AttendanceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentAttendanceController extends Controller
{
    public function __construct(private readonly AttendanceCheckInService $checkInService)
    {
    }

    public function scanPage(Request $request): Response
    {
        abort_unless($request->user()?->isStudent(), 403);

        $sessionId = (int) $request->query('sid');
        $token = (string) $request->query('t');

        $session = AttendanceSession::query()
            ->where('id', $sessionId)
            ->where('school_id', $request->user()->school_id)
            ->firstOrFail();

        return Inertia::render('Attendance/StudentScan', [
            'session' => [
                'id' => $session->id,
                'location_validation' => (bool) $session->location_validation,
            ],
            'token' => $token,
        ]);
    }

    public function checkIn(AttendanceCheckInRequest $request): JsonResponse
    {
        $student = $request->user();
        abort_unless($student?->isStudent(), 403);

        $session = AttendanceSession::query()
            ->where('id', (int) $request->input('session_id'))
            ->where('school_id', $student->school_id)
            ->firstOrFail();

        try {
            $record = $this->checkInService->checkIn(
                $session,
                $student,
                (string) $request->input('token'),
                $request->filled('lat') ? (float) $request->input('lat') : null,
                $request->filled('lng') ? (float) $request->input('lng') : null,
                $request->ip(),
                substr((string) $request->userAgent(), 0, 255)
            );

            return response()->json([
                'message' => 'Absensi berhasil direkam.',
                'data' => [
                    'status' => $record->status,
                    'scanned_at' => optional($record->scanned_at)->toDateTimeString(),
                    'late_minutes' => $record->late_minutes,
                ],
            ]);
        } catch (AttendanceException $exception) {
            $reason = $exception->getMessage();
            return response()->json([
                'message' => $this->humanizeReason($reason),
                'reason' => $reason,
            ], 422);
        }
    }

    private function humanizeReason(string $reason): string
    {
        return match ($reason) {
            'duplicate_attendance' => 'Anda sudah melakukan absensi pada sesi ini.',
            'expired_token' => 'QR kadaluarsa, silakan scan ulang QR terbaru.',
            'invalid_signature', 'invalid_slot', 'invalid_token_format', 'invalid_payload', 'invalid_payload_content' => 'Token QR tidak valid. Scan ulang dari layar guru.',
            'session_not_started' => 'Sesi belum dimulai.',
            'session_expired', 'session_not_open' => 'Sesi sudah ditutup atau belum dibuka.',
            'student_not_enrolled' => 'Anda tidak terdaftar di kelas sesi ini.',
            'location_required' => 'Lokasi wajib diaktifkan untuk sesi ini.',
            'out_of_radius' => 'Anda berada di luar radius lokasi absensi.',
            default => 'Absensi gagal diproses.',
        };
    }
}
