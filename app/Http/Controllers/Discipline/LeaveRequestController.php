<?php

namespace App\Http\Controllers\Discipline;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function studentIndex(Request $request): Response
    {
        $student = $request->user();
        abort_unless($student?->isStudent(), 403);

        $rows = LeaveRequest::query()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (LeaveRequest $item) {
                return [
                    'id' => $item->id,
                    'request_date' => optional($item->request_date)->format('Y-m-d'),
                    'type' => $item->type,
                    'status' => $item->status,
                    'reason' => $item->reason,
                    'decision_note' => $item->decision_note,
                    'decided_at' => optional($item->decided_at)?->format('Y-m-d H:i'),
                    'created_at' => optional($item->created_at)?->format('Y-m-d H:i'),
                ];
            })
            ->values();

        return Inertia::render('Discipline/StudentLeaveRequests', [
            'requests' => $rows,
        ]);
    }

    public function studentStore(Request $request): RedirectResponse
    {
        $student = $request->user();
        abort_unless($student?->isStudent(), 403);

        $payload = $request->validate([
            'request_date' => ['required', 'date', 'after_or_equal:'.CarbonImmutable::now()->subDays(7)->toDateString()],
            'type' => ['required', Rule::in(['izin', 'sakit', 'dispensasi'])],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $record = LeaveRequest::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'request_date' => $payload['request_date'],
            'type' => $payload['type'],
            'reason' => $payload['reason'],
            'status' => 'pending',
        ]);

        $this->activityLogService->log(
            schoolId: (int) $student->school_id,
            actor: $student,
            action: 'leave_request.created',
            targetType: 'leave_request',
            targetId: (int) $record->id,
            meta: ['type' => $record->type, 'request_date' => $record->request_date?->toDateString()],
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return back()->with('success', 'Pengajuan izin berhasil dikirim.');
    }

    public function staffIndex(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor?->canManageDiscipline(), 403);

        $status = (string) $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = LeaveRequest::query()
            ->with('student:id,name')
            ->where('school_id', $actor->school_id)
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $rows = $query->limit(150)->get()->map(function (LeaveRequest $item) {
            return [
                'id' => $item->id,
                'student_name' => $item->student?->name,
                'request_date' => optional($item->request_date)->format('Y-m-d'),
                'type' => $item->type,
                'status' => $item->status,
                'reason' => $item->reason,
                'decision_note' => $item->decision_note,
                'decided_at' => optional($item->decided_at)?->format('Y-m-d H:i'),
            ];
        })->values();

        $counts = [
            'pending' => LeaveRequest::query()->where('school_id', $actor->school_id)->where('status', 'pending')->count(),
            'approved' => LeaveRequest::query()->where('school_id', $actor->school_id)->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::query()->where('school_id', $actor->school_id)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Discipline/LeaveRequestsStaff', [
            'rows' => $rows,
            'status_filter' => $status,
            'counts' => $counts,
        ]);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor?->canManageDiscipline(), 403);
        abort_unless((int) $leaveRequest->school_id === (int) $actor->school_id, 404);

        $payload = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $leaveRequest->status = $payload['decision'];
        $leaveRequest->decision_note = $payload['decision_note'] ?? null;
        $leaveRequest->decided_by = $actor->id;
        $leaveRequest->decided_at = CarbonImmutable::now();
        $leaveRequest->save();

        $this->activityLogService->log(
            schoolId: (int) $actor->school_id,
            actor: $actor,
            action: 'leave_request.'.$payload['decision'],
            targetType: 'leave_request',
            targetId: (int) $leaveRequest->id,
            meta: ['student_id' => $leaveRequest->student_id],
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return back()->with('success', 'Pengajuan berhasil diproses.');
    }
}
