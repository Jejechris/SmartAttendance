<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        [$school] = $this->makeSchoolFixture();
        $teacher = $this->makeUser($school->id, 'teacher');

        $this->actingAs($teacher)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_school_admin_can_access_admin_dashboard(): void
    {
        [$school] = $this->makeSchoolFixture();
        $admin = $this->makeUser($school->id, 'school_admin');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_student_cannot_access_discipline_pages(): void
    {
        [$school] = $this->makeSchoolFixture();
        $student = $this->makeUser($school->id, 'student');

        $this->actingAs($student)
            ->get('/discipline/violations')
            ->assertForbidden();
    }

    public function test_teacher_can_access_discipline_pages(): void
    {
        [$school] = $this->makeSchoolFixture();
        $teacher = $this->makeUser($school->id, 'teacher');

        $this->actingAs($teacher)
            ->get('/discipline/violations')
            ->assertOk();
    }

    public function test_student_id_verify_rejects_token_from_other_school(): void
    {
        [$schoolA] = $this->makeSchoolFixture('SCH-A');
        [$schoolB] = $this->makeSchoolFixture('SCH-B');

        $teacherA = $this->makeUser($schoolA->id, 'teacher');
        $studentB = $this->makeUser($schoolB->id, 'student');

        $token = $this->makeStudentToken($studentB->id, $schoolB->id);

        $this->actingAs($teacherA)
            ->get('/student/id/verify?token='.urlencode($token))
            ->assertOk()
            ->assertSee('Token berasal dari sekolah yang berbeda.');
    }

    private function makeSchoolFixture(?string $code = null): array
    {
        $school = School::create([
            'name' => 'Sekolah Test '.($code ?? Str::upper(Str::random(4))),
            'code' => $code ?? Str::upper(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
        ]);

        return [$school];
    }

    private function makeUser(int $schoolId, string $role): User
    {
        return User::create([
            'name' => Str::title($role).' User',
            'email' => Str::lower($role).'-'.Str::random(6).'@example.test',
            'password' => 'password',
            'school_id' => $schoolId,
            'role' => $role,
        ]);
    }

    private function makeStudentToken(int $studentId, int $schoolId): string
    {
        $payload = [
            'sid' => $studentId,
            'sch' => $schoolId,
            'exp' => now()->addDay()->timestamp,
            'nonce' => Str::random(10),
        ];

        $encoded = rtrim(strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, config('app.key'));

        return $encoded.'.'.$signature;
    }
}
