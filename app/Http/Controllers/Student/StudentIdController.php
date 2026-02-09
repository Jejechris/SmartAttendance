<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentIdController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function show(Request $request): Response
    {
        $student = $request->user();
        abort_unless($student?->isStudent(), 403);

        $activeClass = $student->classesAsStudent()
            ->wherePivot('is_active', true)
            ->orderBy('school_classes.name')
            ->first();

        $token = $this->makeToken($student->id, $student->school_id);

        return Inertia::render('Student/StudentId', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'class_name' => $activeClass?->name,
            ],
            'verify_url' => route('student.id.verify', ['token' => $token]),
            'expires_at' => CarbonImmutable::now()->addDay()->toIso8601String(),
        ]);
    }

    public function verify(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor?->canManageDiscipline(), 403);

        $token = (string) $request->query('token', '');
        $verified = $this->verifyToken($token);

        if (! $verified['valid']) {
            $this->activityLogService->log(
                schoolId: (int) $actor->school_id,
                actor: $actor,
                action: 'student_id.verify_failed',
                meta: ['reason' => $verified['reason'] ?? 'invalid_token'],
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return Inertia::render('Student/StudentIdVerify', [
                'valid' => false,
                'reason' => $verified['reason'] ?? 'Token tidak valid.',
            ]);
        }

        if ((int) $verified['school_id'] !== (int) $actor->school_id) {
            $this->activityLogService->log(
                schoolId: (int) $actor->school_id,
                actor: $actor,
                action: 'student_id.verify_failed',
                meta: ['reason' => 'school_mismatch'],
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return Inertia::render('Student/StudentIdVerify', [
                'valid' => false,
                'reason' => 'Token berasal dari sekolah yang berbeda.',
            ]);
        }

        $student = User::query()
            ->where('id', $verified['student_id'])
            ->where('school_id', $actor->school_id)
            ->where('role', 'student')
            ->first();

        if (! $student) {
            return Inertia::render('Student/StudentIdVerify', [
                'valid' => false,
                'reason' => 'Data siswa tidak ditemukan di sekolah Anda.',
            ]);
        }

        $class = $student->classesAsStudent()->wherePivot('is_active', true)->orderBy('school_classes.name')->first();

        $this->activityLogService->log(
            schoolId: (int) $actor->school_id,
            actor: $actor,
            action: 'student_id.verified',
            targetType: 'user',
            targetId: (int) $student->id,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return Inertia::render('Student/StudentIdVerify', [
            'valid' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'class_name' => $class?->name,
            ],
        ]);
    }

    private function makeToken(int $studentId, int $schoolId): string
    {
        $payload = [
            'sid' => $studentId,
            'sch' => $schoolId,
            'exp' => CarbonImmutable::now()->addDay()->timestamp,
            'nonce' => Str::random(10),
        ];

        $encoded = rtrim(strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, config('app.key'));

        return $encoded.'.'.$signature;
    }

    private function verifyToken(string $token): array
    {
        [$encoded, $signature] = array_pad(explode('.', $token, 2), 2, null);
        if (! $encoded || ! $signature) {
            return ['valid' => false, 'reason' => 'Format token tidak valid.'];
        }

        $expected = hash_hmac('sha256', $encoded, config('app.key'));
        if (! hash_equals($expected, $signature)) {
            return ['valid' => false, 'reason' => 'Signature token tidak valid.'];
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/').str_repeat('=', (4 - strlen($encoded) % 4) % 4), true);
        if ($decoded === false) {
            return ['valid' => false, 'reason' => 'Payload token rusak.'];
        }

        $payload = json_decode($decoded, true);
        if (! is_array($payload) || ! isset($payload['sid'], $payload['sch'], $payload['exp'])) {
            return ['valid' => false, 'reason' => 'Payload token tidak lengkap.'];
        }

        if (CarbonImmutable::now()->timestamp > (int) $payload['exp']) {
            return ['valid' => false, 'reason' => 'Token Student ID sudah kadaluarsa.'];
        }

        return [
            'valid' => true,
            'student_id' => (int) $payload['sid'],
            'school_id' => (int) $payload['sch'],
        ];
    }
}
