<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $school = null;
        if ($request->user()?->school) {
            $school = [
                'id' => $request->user()->school->id,
                'name' => $request->user()->school->name,
                'display_name' => $request->user()->school->display_name,
                'logo_url' => $request->user()->school->logo_url,
                'timezone' => $request->user()->school->timezone,
            ];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'role' => $request->user()->role,
                        'school_id' => $request->user()->school_id,
                    ]
                    : null,
            ],
            'school' => $school,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
