<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\User;
use App\Services\AttendanceCheckInService;
use App\Services\AttendanceException;
use App\Services\AttendanceTokenService;
use Illuminate\Console\Command;

class SimulateAttendanceClassCommand extends Command
{
    protected $signature = 'attendance:simulate-class
        {session_id : ID sesi absensi}
        {--students=36 : Jumlah siswa yang disimulasikan}
        {--with-location : Sertakan koordinat geofence valid}';

    protected $description = 'Simulasi check-in 1 kelas untuk pengujian beban ringan.';

    public function handle(
        AttendanceTokenService $tokenService,
        AttendanceCheckInService $checkInService
    ): int {
        $sessionId = (int) $this->argument('session_id');
        $limit = max(1, (int) $this->option('students'));

        $session = AttendanceSession::query()->find($sessionId);
        if (!$session) {
            $this->error('Session tidak ditemukan.');
            return self::FAILURE;
        }

        $studentIds = ClassStudent::query()
            ->where('school_id', $session->school_id)
            ->where('class_id', $session->class_id)
            ->where('is_active', true)
            ->limit($limit)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            $this->error('Tidak ada siswa aktif di kelas ini.');
            return self::FAILURE;
        }

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->orderBy('id')
            ->get();

        $success = 0;
        $failed = 0;

        foreach ($students as $student) {
            $token = $tokenService->generateForSession($session)['token'];

            $lat = null;
            $lng = null;
            if ($this->option('with-location') && $session->location_validation) {
                $lat = (float) $session->center_lat;
                $lng = (float) $session->center_lng;
            }

            try {
                $checkInService->checkIn(
                    $session,
                    $student,
                    $token,
                    $lat,
                    $lng,
                    '127.0.0.1',
                    'attendance:simulate-class'
                );
                $success++;
            } catch (AttendanceException $exception) {
                $failed++;
                $this->warn("Student {$student->id} gagal: {$exception->getMessage()}");
            }
        }

        $this->info("Simulasi selesai. Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
