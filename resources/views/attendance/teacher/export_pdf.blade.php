<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Sesi #{{ $session->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { margin-bottom: 4px; }
        p { margin-top: 0; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #222; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h1>Rekap Absensi Sesi #{{ $session->id }}</h1>
    <p>Kelas: {{ $session->schoolClass?->name }}</p>
    <p>Mapel: {{ $session->subject?->name }}</p>
    <p>Waktu: {{ optional($session->started_at)->toDateTimeString() }} - {{ optional($session->ended_at)->toDateTimeString() }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Status</th>
                <th>Waktu Scan</th>
                <th>Terlambat (menit)</th>
                <th>Jarak (meter)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record->student?->name }}</td>
                    <td>{{ strtoupper($record->status) }}</td>
                    <td>{{ optional($record->scanned_at)->toDateTimeString() }}</td>
                    <td>{{ $record->late_minutes }}</td>
                    <td>{{ $record->distance_meters }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
