<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor?->isSchoolAdmin(), 403);

        $action = (string) $request->query('action', '');
        $actorId = $request->query('actor_id');

        $query = ActivityLog::query()
            ->with('actor:id,name')
            ->where('school_id', $actor->school_id)
            ->orderByDesc('created_at');

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($actorId) {
            $query->where('actor_id', $actorId);
        }

        $rows = $query->limit(300)->get()->map(function (ActivityLog $log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'actor_name' => $log->actor?->name,
                'target_type' => $log->target_type,
                'target_id' => $log->target_id,
                'meta' => $log->meta,
                'ip_address' => $log->ip_address,
                'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
            ];
        })->values();

        $actors = User::query()
            ->where('school_id', $actor->school_id)
            ->whereIn('role', ['school_admin', 'teacher'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $actions = ActivityLog::query()
            ->where('school_id', $actor->school_id)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        return Inertia::render('Admin/ActivityLogs', [
            'rows' => $rows,
            'actors' => $actors,
            'actions' => $actions,
            'filters' => [
                'action' => $action,
                'actor_id' => $actorId ? (int) $actorId : null,
            ],
        ]);
    }
}
