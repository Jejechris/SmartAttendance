<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function export(Request $request, AttendanceSession $session)
    {
        $this->authorizeAccess($request, $session);

        $session->load([
            'schoolClass',
            'subject',
            'records.student',
        ]);

        $format = strtolower((string) $request->query('format', 'xlsx'));

        $this->activityLogService->log(
            schoolId: (int) $session->school_id,
            actor: $request->user(),
            action: 'attendance_session.exported',
            targetType: 'attendance_session',
            targetId: (int) $session->id,
            meta: ['format' => $format],
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        if ($format === 'pdf') {
            return $this->exportPdf($session);
        }

        return $this->exportExcelFriendlyCsv($session);
    }

    private function authorizeAccess(Request $request, AttendanceSession $session): void
    {
        $user = $request->user();
        abort_unless((int) $session->school_id === (int) $user->school_id, 404);

        $canAccess = $user->role === 'school_admin' || (int) $session->teacher_id === (int) $user->id;
        abort_unless($canAccess, 403);
    }

    private function exportExcelFriendlyCsv(AttendanceSession $session): StreamedResponse
    {
        $filename = sprintf(
            'attendance_session_%d_%s.csv',
            $session->id,
            CarbonImmutable::now()->format('Ymd_His')
        );

        $records = $session->records->sortBy('student.name')->values();

        return response()->streamDownload(function () use ($records, $session) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Session ID', $session->id]);
            fputcsv($out, ['Class', $session->schoolClass?->name]);
            fputcsv($out, ['Subject', $session->subject?->name]);
            fputcsv($out, ['Started At', optional($session->started_at)->toDateTimeString()]);
            fputcsv($out, ['Ended At', optional($session->ended_at)->toDateTimeString()]);
            fputcsv($out, []);

            fputcsv($out, ['No', 'Nama Siswa', 'Status', 'Waktu Scan', 'Terlambat (menit)', 'Jarak (meter)']);

            foreach ($records as $index => $record) {
                fputcsv($out, [
                    $index + 1,
                    $record->student?->name,
                    $record->status,
                    optional($record->scanned_at)->toDateTimeString(),
                    $record->late_minutes,
                    $record->distance_meters,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportPdf(AttendanceSession $session)
    {
        $viewData = [
            'session' => $session,
            'records' => $session->records->sortBy('student.name')->values(),
        ];

        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            /** @var \Barryvdh\DomPDF\PDF $pdf */
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('attendance.teacher.export_pdf', $viewData);

            $filename = sprintf('attendance_session_%d.pdf', $session->id);

            return $pdf->download($filename);
        }

        return response()
            ->view('attendance.teacher.export_pdf', $viewData)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
