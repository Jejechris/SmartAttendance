<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttendanceCheckInService;
use App\Services\AttendanceException;
use App\Services\AttendanceSessionService;
use App\Services\AttendanceTokenService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_check_in_once_per_session(): void
    {
        [$session, $student] = $this->makeFixture(false);
        $tokenService = app(AttendanceTokenService::class);
        $checkInService = app(AttendanceCheckInService::class);

        $token = $tokenService->generateForSession($session)['token'];
        $record = $checkInService->checkIn($session, $student, $token, null, null, '127.0.0.1', 'PHPUnit');

        $this->assertSame('hadir', $record->status);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_duplicate_check_in_is_rejected(): void
    {
        [$session, $student] = $this->makeFixture(false);
        $tokenService = app(AttendanceTokenService::class);
        $checkInService = app(AttendanceCheckInService::class);

        $token = $tokenService->generateForSession($session)['token'];
        $checkInService->checkIn($session, $student, $token, null, null, '127.0.0.1', 'PHPUnit');

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('duplicate_attendance');
        $checkInService->checkIn($session, $student, $token, null, null, '127.0.0.1', 'PHPUnit');
    }

    public function test_out_of_radius_is_rejected_when_location_enabled(): void
    {
        [$session, $student] = $this->makeFixture(true);
        $token = app(AttendanceTokenService::class)->generateForSession($session)['token'];

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('out_of_radius');

        app(AttendanceCheckInService::class)->checkIn(
            $session,
            $student,
            $token,
            -6.2200000,
            106.8500000,
            '127.0.0.1',
            'PHPUnit'
        );
    }

    public function test_close_session_marks_missing_students_as_alpha(): void
    {
        [$session, $student, $teacher, $class] = $this->makeFixture(false, true);

        $token = app(AttendanceTokenService::class)->generateForSession($session)['token'];
        app(AttendanceCheckInService::class)->checkIn($session, $student, $token, null, null, '127.0.0.1', 'PHPUnit');

        $otherStudent = User::create([
            'name' => 'Siswa B',
            'email' => 'siswa-b@example.test',
            'password' => Hash::make('password'),
            'school_id' => $teacher->school_id,
            'role' => 'student',
        ]);

        ClassStudent::create([
            'school_id' => $teacher->school_id,
            'class_id' => $class->id,
            'student_id' => $otherStudent->id,
            'is_active' => true,
        ]);

        app(AttendanceSessionService::class)->closeSessionAndMarkAlpha($session, $teacher);

        $alpha = AttendanceRecord::query()
            ->where('session_id', $session->id)
            ->where('student_id', $otherStudent->id)
            ->first();

        $this->assertNotNull($alpha);
        $this->assertSame('alpha', $alpha->status);
    }

    public function test_close_expired_sessions_command_logic_closes_open_session(): void
    {
        [$session] = $this->makeFixture(false);

        $session->update([
            'ended_at' => CarbonImmutable::now()->subMinute(),
            'status' => 'open',
        ]);

        $count = app(AttendanceSessionService::class)->closeExpiredSessions();

        $this->assertSame(1, $count);
        $this->assertSame('closed', $session->fresh()->status);
    }

    private function makeFixture(bool $locationValidation, bool $returnExtended = false): array
    {
        $school = School::create([
            'name' => 'Sekolah Test',
            'code' => 'SCH-TST',
            'timezone' => 'Asia/Jakarta',
        ]);

        $teacher = User::create([
            'name' => 'Guru Test',
            'email' => 'guru@example.test',
            'password' => Hash::make('password'),
            'school_id' => $school->id,
            'role' => 'teacher',
        ]);

        $student = User::create([
            'name' => 'Siswa Test',
            'email' => 'siswa@example.test',
            'password' => Hash::make('password'),
            'school_id' => $school->id,
            'role' => 'student',
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
        ]);

        ClassStudent::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'is_active' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Matematika',
            'code' => 'MTK',
        ]);

        $session = AttendanceSession::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'started_at' => CarbonImmutable::now()->subMinutes(5),
            'ended_at' => CarbonImmutable::now()->addMinutes(30),
            'late_tolerance_minutes' => 10,
            'qr_dynamic' => true,
            'qr_rotate_seconds' => 30,
            'location_validation' => $locationValidation,
            'center_lat' => $locationValidation ? -6.2000000 : null,
            'center_lng' => $locationValidation ? 106.8166667 : null,
            'radius_meters' => $locationValidation ? 100 : null,
            'session_secret' => bin2hex(random_bytes(32)),
            'status' => 'open',
            'opened_at' => CarbonImmutable::now()->subMinutes(5),
        ]);

        if ($returnExtended) {
            return [$session, $student, $teacher, $class];
        }

        return [$session, $student];
    }
}
