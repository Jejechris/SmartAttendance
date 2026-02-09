<?php

namespace App\Console\Commands;

use App\Services\AttendanceSessionService;
use Illuminate\Console\Command;

class CloseExpiredAttendanceSessionsCommand extends Command
{
    protected $signature = 'attendance:close-expired';

    protected $description = 'Menutup otomatis sesi absensi yang sudah lewat waktu akhir dan mark alpha.';

    public function handle(AttendanceSessionService $attendanceSessionService): int
    {
        $count = $attendanceSessionService->closeExpiredSessions();

        $this->info("Closed sessions: {$count}");

        return self::SUCCESS;
    }
}
