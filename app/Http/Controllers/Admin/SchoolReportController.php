<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolReportController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function daily(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor?->isSchoolAdmin(), 403);

        $date = (string) $request->query('date', CarbonImmutable::now()->toDateString());
        $rows = $this->buildDailyRows((int) $actor->school_id, $date);

        return Inertia::render('Admin/DailyReport', [
            'date' => $date,
            'rows' => $rows,
        ]);
    }

    public function exportDaily(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->isSchoolAdmin(), 403);

        $date = (string) $request->query('date', CarbonImmutable::now()->toDateString());
        $format = strtolower((string) $request->query('format', 'xlsx'));

        $rows = $this->buildDailyRows((int) $actor->school_id, $date);

        $this->activityLogService->log(
            schoolId: (int) $actor->school_id,
            actor: $actor,
            action: 'report.daily_exported',
            targetType: 'report',
            meta: ['date' => $date, 'format' => $format],
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        if ($format === 'pdf') {
            return $this->exportDailyPdf($date, $rows);
        }

        return $this->exportDailyCsv($date, $rows);
    }

    private function buildDailyRows(int $schoolId, string $date): array
    {
        $classes = SchoolClass::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $totalStudentsByClass = DB::table('class_students')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->select('class_id')
            ->selectRaw('count(*) as total_students')
            ->groupBy('class_id')
            ->pluck('total_students', 'class_id');

        $counts = DB::table('attendance_records as ar')
            ->join('attendance_sessions as s', 's.id', '=', 'ar.session_id')
            ->where('ar.school_id', $schoolId)
            ->whereDate('s.started_at', $date)
            ->select('s.class_id', 'ar.status')
            ->selectRaw('count(*) as total')
            ->groupBy('s.class_id', 'ar.status')
            ->get();

        $mapped = [];
        foreach ($counts as $count) {
            $classId = (int) $count->class_id;
            $status = (string) $count->status;
            $mapped[$classId][$status] = (int) $count->total;
        }

        return $classes->map(function ($class) use ($totalStudentsByClass, $mapped) {
            $classId = (int) $class->id;
            $hadir = $mapped[$classId]['hadir'] ?? 0;
            $terlambat = $mapped[$classId]['terlambat'] ?? 0;
            $alpha = $mapped[$classId]['alpha'] ?? 0;
            $present = $hadir + $terlambat;
            $totalStudents = (int) ($totalStudentsByClass[$classId] ?? 0);
            $attendanceRate = $totalStudents > 0 ? round(($present / $totalStudents) * 100, 1) : 0;

            return [
                'class_id' => $classId,
                'class_name' => $class->name,
                'total_students' => $totalStudents,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'alpha' => $alpha,
                'attendance_rate' => $attendanceRate,
            ];
        })->values()->all();
    }

    private function exportDailyCsv(string $date, array $rows): StreamedResponse
    {
        $filename = sprintf('rekap_harian_%s.csv', str_replace('-', '', $date));

        return response()->streamDownload(function () use ($date, $rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Rekap Harian', $date]);
            fputcsv($out, []);
            fputcsv($out, ['No', 'Kelas', 'Total Siswa', 'Hadir', 'Terlambat', 'Alpha', 'Rate Kehadiran (%)']);

            foreach ($rows as $index => $row) {
                fputcsv($out, [
                    $index + 1,
                    $row['class_name'],
                    $row['total_students'],
                    $row['hadir'],
                    $row['terlambat'],
                    $row['alpha'],
                    $row['attendance_rate'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportDailyPdf(string $date, array $rows)
    {
        $viewData = [
            'date' => $date,
            'rows' => $rows,
        ];

        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            /** @var \Barryvdh\DomPDF\PDF $pdf */
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('reports.daily_summary_pdf', $viewData);

            return $pdf->download(sprintf('rekap_harian_%s.pdf', str_replace('-', '', $date)));
        }

        return response()
            ->view('reports.daily_summary_pdf', $viewData)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
