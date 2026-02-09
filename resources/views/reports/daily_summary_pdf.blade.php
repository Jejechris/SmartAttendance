<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Harian {{ $date }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h1>Rekap Kehadiran Harian</h1>
    <p>Tanggal: {{ $date }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kelas</th>
                <th>Total Siswa</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Alpha</th>
                <th>Rate (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td>{{ $row['total_students'] }}</td>
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['terlambat'] }}</td>
                    <td>{{ $row['alpha'] }}</td>
                    <td>{{ $row['attendance_rate'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
