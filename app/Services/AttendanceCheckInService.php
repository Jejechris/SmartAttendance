<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceScanAttempt;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AttendanceCheckInService
{
    public function __construct(
        private readonly AttendanceTokenService $tokenService,
        private readonly GeofenceService $geofenceService
    ) {
    }

    public function checkIn(
        AttendanceSession $session,
        User $student,
        string $token,
        ?float $lat,
        ?float $lng,
        ?string $ip,
        ?string $userAgent
    ): AttendanceRecord {
        $now = CarbonImmutable::now();
        $tokenSlot = null;

        try {
            $record = DB::transaction(function () use ($session, $student, $token, $lat, $lng, $ip, $userAgent, $now, &$tokenSlot) {
                $lockedSession = AttendanceSession::query()
                    ->lockForUpdate()
                    ->findOrFail($session->id);

                if ((int) $lockedSession->school_id !== (int) $student->school_id) {
                    throw AttendanceException::rejected('school_mismatch');
                }

                if (!$student->isStudent()) {
                    throw AttendanceException::rejected('role_not_student');
                }

                if ($lockedSession->status !== 'open') {
                    throw AttendanceException::rejected('session_not_open');
                }

                if ($now->lt(CarbonImmutable::instance($lockedSession->started_at))) {
                    throw AttendanceException::rejected('session_not_started');
                }

                if ($now->gt(CarbonImmutable::instance($lockedSession->ended_at))) {
                    throw AttendanceException::rejected('session_expired');
                }

                $isEnrolled = ClassStudent::query()
                    ->where('school_id', $lockedSession->school_id)
                    ->where('class_id', $lockedSession->class_id)
                    ->where('student_id', $student->id)
                    ->where('is_active', true)
                    ->exists();

                if (!$isEnrolled) {
                    throw AttendanceException::rejected('student_not_enrolled');
                }

                $verified = $this->tokenService->verifyForSession($lockedSession, $token, 1, $now);
                if (!$verified['valid']) {
                    throw AttendanceException::rejected($verified['reason'] ?? 'invalid_token');
                }

                $tokenSlot = $verified['slot'] ?? null;

                $alreadyRecorded = AttendanceRecord::query()
                    ->where('session_id', $lockedSession->id)
                    ->where('student_id', $student->id)
                    ->exists();

                if ($alreadyRecorded) {
                    throw AttendanceException::rejected('duplicate_attendance');
                }

                $distanceMeters = null;
                if ($lockedSession->location_validation) {
                    if ($lat === null || $lng === null) {
                        throw AttendanceException::rejected('location_required');
                    }

                    if ($lockedSession->center_lat === null || $lockedSession->center_lng === null || $lockedSession->radius_meters === null) {
                        throw AttendanceException::rejected('invalid_session_geofence');
                    }

                    $distanceMeters = $this->geofenceService->haversineMeters(
                        $lat,
                        $lng,
                        (float) $lockedSession->center_lat,
                        (float) $lockedSession->center_lng
                    );

                    if ($distanceMeters > (float) $lockedSession->radius_meters) {
                        throw AttendanceException::rejected('out_of_radius');
                    }
                }

                $lateLimit = $lockedSession->lateLimitAt();
                $isLate = $now->gt($lateLimit);

                $record = AttendanceRecord::create([
                    'school_id' => $lockedSession->school_id,
                    'session_id' => $lockedSession->id,
                    'student_id' => $student->id,
                    'scanned_at' => $now,
                    'status' => $isLate ? 'terlambat' : 'hadir',
                    'late_minutes' => $isLate ? $lateLimit->diffInMinutes($now) : 0,
                    'token_slot' => $tokenSlot,
                    'scan_lat' => $lat,
                    'scan_lng' => $lng,
                    'distance_meters' => $distanceMeters,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);

                AttendanceScanAttempt::create([
                    'school_id' => $lockedSession->school_id,
                    'session_id' => $lockedSession->id,
                    'student_id' => $student->id,
                    'attempted_at' => $now,
                    'token_slot' => $tokenSlot,
                    'result' => 'accepted',
                    'reason_code' => null,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);

                return $record;
            });

            return $record;
        } catch (AttendanceException $exception) {
            $this->logRejectedAttempt($session, $student, $tokenSlot, $exception->getMessage(), $now, $ip, $userAgent);
            throw $exception;
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                $this->logRejectedAttempt($session, $student, $tokenSlot, 'duplicate_attendance', $now, $ip, $userAgent);
                throw AttendanceException::rejected('duplicate_attendance');
            }

            throw $exception;
        }
    }

    private function logRejectedAttempt(
        AttendanceSession $session,
        User $student,
        ?int $tokenSlot,
        string $reason,
        CarbonImmutable $attemptedAt,
        ?string $ip,
        ?string $userAgent
    ): void {
        AttendanceScanAttempt::create([
            'school_id' => $session->school_id,
            'session_id' => $session->id,
            'student_id' => $student->id,
            'attempted_at' => $attemptedAt,
            'token_slot' => $tokenSlot,
            'result' => 'rejected',
            'reason_code' => $reason,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
