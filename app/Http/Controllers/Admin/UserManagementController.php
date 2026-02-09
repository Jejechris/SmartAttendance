<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor?->isSchoolAdmin(), 403);

        $rows = User::query()
            ->where('school_id', $actor->school_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'created_at'])
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => optional($user->created_at)->format('Y-m-d H:i'),
                ];
            })
            ->values();

        return Inertia::render('Admin/UserManagement', [
            'rows' => $rows,
            'roles' => ['school_admin', 'teacher', 'student'],
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor?->isSchoolAdmin(), 403);
        abort_unless((int) $user->school_id === (int) $actor->school_id, 404);

        $payload = $request->validate([
            'role' => ['required', Rule::in(['school_admin', 'teacher', 'student'])],
        ]);

        if ((int) $user->id === (int) $actor->id && $payload['role'] !== 'school_admin') {
            return back()->with('error', 'Anda tidak bisa menurunkan role akun sendiri.');
        }

        if ($user->role === 'school_admin' && $payload['role'] !== 'school_admin') {
            $adminCount = User::query()
                ->where('school_id', $actor->school_id)
                ->where('role', 'school_admin')
                ->count();

            if ($adminCount <= 1) {
                return back()->with('error', 'Sekolah harus memiliki minimal 1 school admin.');
            }
        }

        $oldRole = $user->role;
        $user->role = $payload['role'];
        $user->save();

        $this->activityLogService->log(
            schoolId: (int) $actor->school_id,
            actor: $actor,
            action: 'user.role_updated',
            targetType: 'user',
            targetId: (int) $user->id,
            meta: ['from' => $oldRole, 'to' => $user->role],
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return back()->with('success', 'Role user berhasil diperbarui.');
    }
}
