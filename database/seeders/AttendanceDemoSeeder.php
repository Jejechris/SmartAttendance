<?php

namespace Database\Seeders;

use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::firstOrCreate(
            ['code' => 'SMK001'],
            ['name' => 'SMK Demo Nusantara', 'timezone' => 'Asia/Jakarta']
        );

        $teacher = User::firstOrCreate(
            ['email' => 'guru.absen@demo.sch.id'],
            [
                'name' => 'Guru Absensi',
                'password' => Hash::make('password'),
                'school_id' => $school->id,
                'role' => 'teacher',
            ]
        );

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'XII RPL 1'],
            ['grade_level' => 'XII']
        );

        $subject = Subject::firstOrCreate(
            ['school_id' => $school->id, 'code' => 'RPL-ABSEN'],
            ['name' => 'Pemrograman Web']
        );

        for ($i = 1; $i <= 36; $i++) {
            $student = User::firstOrCreate(
                ['email' => sprintf('siswa%02d@demo.sch.id', $i)],
                [
                    'name' => sprintf('Siswa %02d', $i),
                    'password' => Hash::make('password'),
                    'school_id' => $school->id,
                    'role' => 'student',
                ]
            );

            ClassStudent::firstOrCreate([
                'school_id' => $school->id,
                'class_id' => $class->id,
                'student_id' => $student->id,
            ], [
                'is_active' => true,
            ]);
        }

        AttendanceSession::firstOrCreate([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'started_at' => CarbonImmutable::now()->subMinutes(5),
            'ended_at' => CarbonImmutable::now()->addMinutes(55),
        ], [
            'late_tolerance_minutes' => 10,
            'qr_dynamic' => true,
            'qr_rotate_seconds' => 30,
            'location_validation' => true,
            'center_lat' => -6.2000000,
            'center_lng' => 106.8166667,
            'radius_meters' => 80,
            'session_secret' => bin2hex(random_bytes(32)),
            'status' => 'open',
            'opened_at' => CarbonImmutable::now()->subMinutes(5),
        ]);
    }
}
