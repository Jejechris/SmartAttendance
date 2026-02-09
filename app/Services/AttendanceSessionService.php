<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceSessionService
{
    public function openSession(AttendanceSession $session, User $actor): AttendanceSession
    {
        $this->assertTeacherAccess($session, $actor);

        if ($session->status === 'closed') {
            throw AttendanceException::rejected('session_already_closed');
        }

        if ($session->status === 'open') {
            return $session;
        }

        $session->status = 'open';
        $session->opened_at = CarbonImmutable::now();
        if (empty($session->session_secret)) {
            $session->session_secret = bin2hex(random_bytes(32));
        }
        $session->save();

        return $session->fresh();
    }

    public function closeSessionAndMarkAlpha(AttendanceSession $session, User $actor): AttendanceSession
    {
        $this->assertTeacherAccess($session, $actor);
        $this->closeAndMarkAlpha($session->id);

        return $session->fresh();
    }

    public function closeExpiredSessions(): int
    {
        $sessionIds = AttendanceSession::query()
            ->where('status', 'open')
            ->where('ended_at', '<=', CarbonImmutable::now())
            ->pluck('id');

        $closedCount = 0;
        foreach ($sessionIds as $sessionId) {
            $this->closeAndMarkAlpha((int) $sessionId);
            $closedCount++;
        }

        return $closedCount;
    }

    public function assertTeacherAccess(AttendanceSession $session, User $actor): void
    {
        if ((int) $session->school_id !== (int) $actor->school_id) {
            throw AttendanceException::rejected('school_mismatch');
        }

        $canManage = $actor->role === 'school_admin' || (int) $session->teacher_id === (int) $actor->id;
        if (!$canManage) {
            throw AttendanceException::rejected('forbidden');
        }
    }

    private function closeAndMarkAlpha(int $sessionId): void
    {
        DB::transaction(function () use ($sessionId) {
            $lockedSession = AttendanceSession::query()->lockForUpdate()->findOrFail($sessionId);

            if ($lockedSession->status !== 'closed') {
                $lockedSession->status = 'closed';
                $lockedSession->closed_at = CarbonImmutable::now();
                $lockedSession->save();
            }

            $this->insertAlphaRowsForMissingStudents($lockedSession);
        });
    }

    private function insertAlphaRowsForMissingStudents(AttendanceSession $session): void
    {
        $studentIds = ClassStudent::query()
            ->where('school_id', $session->school_id)
            ->where('class_id', $session->class_id)
            ->where('is_active', true)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return;
        }

        $existing = AttendanceRecord::query()
            ->where('session_id', $session->id)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        $missingIds = $studentIds->diff($existing)->values();
        if ($missingIds->isEmpty()) {
            return;
        }

        $now = CarbonImmutable::now();
        $rows = [];
        foreach ($missingIds as $studentId) {
            $rows[] = [
                'school_id' => $session->school_id,
                'session_id' => $session->id,
                'student_id' => $studentId,
                'scanned_at' => null,
                'status' => 'alpha',
                'late_minutes' => 0,
                'token_slot' => null,
                'scan_lat' => null,
                'scan_lng' => null,
                'distance_meters' => null,
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        AttendanceRecord::query()->insert($rows);
    }
}
