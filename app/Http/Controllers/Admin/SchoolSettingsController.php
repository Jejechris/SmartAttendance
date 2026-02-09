<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function branding(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isSchoolAdmin(), 403);

        $school = $user->school()->firstOrFail();

        return Inertia::render('Admin/BrandingSettings', [
            'school' => [
                'name' => $school->name,
                'display_name' => $school->display_name,
                'logo_url' => $school->logo_url,
                'timezone' => $school->timezone,
            ],
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isSchoolAdmin(), 403);

        $payload = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'timezone' => ['required', 'timezone'],
        ]);

        $school = $user->school()->firstOrFail();
        $school->display_name = $payload['display_name'] ?? null;
        $school->logo_url = $payload['logo_url'] ?? null;
        $school->timezone = $payload['timezone'];
        $school->save();

        $this->activityLogService->log(
            schoolId: (int) $school->id,
            actor: $user,
            action: 'school_branding.updated',
            targetType: 'school',
            targetId: (int) $school->id,
            meta: [
                'display_name' => $school->display_name,
                'timezone' => $school->timezone,
            ],
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return back()->with('success', 'Branding sekolah berhasil diperbarui.');
    }
}
