# SmartAttendance - Modul Absensi QR Dinamis (Laravel + MySQL)

Implementasi ini berisi modul absensi QR dinamis untuk SaaS sekolah (multi-tenant via `school_id`) dengan fokus:

- sederhana, aman, dan realistis untuk solo dev,
- stabil di browser HP siswa,
- anti-kecurangan tanpa over-engineering,
- tanpa WebSocket (realtime via polling).

## Fitur yang sudah diimplementasi

1. Guru buat sesi absensi (`attendance_sessions`)
2. QR dinamis dengan token HMAC yang berganti tiap X detik
3. Siswa scan via browser (login), check-in ke server
4. Validasi anti-kecurangan:
- tenant (`school_id`)
- role/user
- token valid + expiry
- waktu sesi
- 1 siswa 1 record per sesi
- geofence opsional (Haversine)
5. Rekap realtime guru (polling endpoint)
6. Export rekap (CSV Excel-friendly + PDF fallback HTML/DomPDF)
7. Auto-mark `alpha` saat sesi ditutup
8. Seeder demo 1 kelas 36 siswa
9. Command simulasi 36 siswa untuk uji kelas
10. Auto-close sesi lewat scheduler command
11. Feature test dasar flow absensi

## Struktur utama file

- `database/migrations/*attendance*.php`
- `app/Models/AttendanceSession.php`
- `app/Models/AttendanceRecord.php`
- `app/Models/AttendanceScanAttempt.php`
- `app/Services/AttendanceTokenService.php`
- `app/Services/AttendanceCheckInService.php`
- `app/Services/AttendanceSessionService.php`
- `app/Services/GeofenceService.php`
- `app/Http/Controllers/Teacher/AttendanceSessionController.php`
- `app/Http/Controllers/StudentAttendanceController.php`
- `app/Http/Controllers/AttendanceExportController.php`
- `app/Http/Requests/Teacher/AttendanceSessionStoreRequest.php`
- `app/Http/Requests/AttendanceCheckInRequest.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/views/app.blade.php`
- `resources/js/Pages/Attendance/TeacherSessionsIndex.vue`
- `resources/js/Pages/Attendance/TeacherSessionShow.vue`
- `resources/js/Pages/Attendance/StudentScan.vue`
- `resources/js/Pages/Attendance/StudentHome.vue`
- `resources/js/Layouts/DashboardLayout.vue`
- `resources/js/Components/Attendance/*.vue`
- `resources/views/attendance/teacher/export_pdf.blade.php`
- `routes/web.php`
- `database/seeders/AttendanceDemoSeeder.php`
- `tests/Feature/AttendanceFlowTest.php`

## Endpoint

Semua endpoint di bawah middleware `auth`.

### Guru

- `GET /teacher/attendance/sessions` (dashboard/list + form create)
- `POST /teacher/attendance/sessions`
- `GET /teacher/attendance/sessions/{session}`
- `POST /teacher/attendance/sessions/{session}/open`
- `POST /teacher/attendance/sessions/{session}/close`
- `GET /teacher/attendance/sessions/{session}/qr`
- `GET /teacher/attendance/sessions/{session}/realtime`
- `GET /teacher/attendance/sessions/{session}/export?format=xlsx|pdf`

### Siswa

- `GET /attendance/scan?sid={session_id}&t={token}`
- `POST /attendance/check-in`

## Cara kerja QR dinamis

Token berisi payload:

- `sid` (session id)
- `slot` (berdasarkan waktu/rotasi)
- `exp` (expiry)

Token ditandatangani dengan `HMAC_SHA256` memakai `session_secret`.
Server memverifikasi:

- signature valid,
- `sid` cocok,
- token belum expired,
- slot masih diterima (dengan toleransi 1 slot untuk jaringan lambat).

## Geofence

Saat `location_validation = true`, server menghitung jarak scan ke titik kelas dengan Haversine. Jika melebihi `radius_meters`, absensi ditolak.

## Testing 1 kelas (36 siswa)

1. Seed data demo:

```bash
php artisan db:seed --class=AttendanceDemoSeeder
```

2. Login sebagai guru demo dan buka sesi.
3. Akses halaman sesi guru lalu scan dari akun siswa.
4. Validasi skenario:
- duplicate check-in ditolak,
- token expired ditolak,
- lokasi luar radius ditolak (jika aktif),
- close session menghasilkan `alpha` otomatis.

5. Jalankan test:

```bash
php artisan test --filter=AttendanceFlowTest
```

6. Jalankan simulasi check-in 36 siswa:

```bash
php artisan attendance:simulate-class {session_id} --students=36 --with-location
```

## Otomatisasi harian

Command auto-close sesi:

```bash
php artisan attendance:close-expired
```

Scheduler diset di `routes/console.php` (`everyMinute()`).
Pastikan cron Laravel aktif:

```bash
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

## Menjalankan aplikasi

```bash
php artisan serve
npm run dev
```

Buka `http://127.0.0.1:8000/login`.

Akun demo:

- Guru: `guru.absen@demo.sch.id` / `password`
- Siswa contoh: `siswa01@demo.sch.id` / `password`

## Catatan dependency

- PDF native sudah aktif dengan `barryvdh/laravel-dompdf`.
- Export saat ini default CSV (Excel-friendly). Jika ingin XLSX native, bisa tambah `maatwebsite/excel`.

## Catatan environment saat implementasi

Runtime PHP + Composer sudah diinstall via `winget` dan validasi eksekusi (`migrate`, `test`, command simulasi) sudah dijalankan.

Panduan deployment ringkas tersedia di `DEPLOYMENT.md`.
Contoh kontrak endpoint tersedia di `docs/API.md`.
