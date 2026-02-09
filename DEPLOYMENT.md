# Deployment Checklist (Laravel)

## 1. Prasyarat

- PHP 8.2+
- Composer 2+
- MySQL 8+
- Ekstensi PHP umum Laravel (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`)

## 2. Instal dependensi PDF

```bash
composer require barryvdh/laravel-dompdf
```

## 3. Migrasi dan seed demo

```bash
php artisan migrate
php artisan db:seed --class=AttendanceDemoSeeder
```

## 4. Jadwalkan auto-close sesi

Tambahkan cron:

```bash
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

## 5. Smoke test cepat

- Login guru demo
- Buka `/teacher/attendance/sessions`
- Buat sesi baru
- Klik `Buka Sesi`
- Scan QR dari akun siswa
- Tutup sesi dan cek `alpha` otomatis

## 6. Uji 36 siswa

```bash
php artisan attendance:simulate-class {session_id} --students=36 --with-location
```

## 7. Monitoring tabel penting

- `attendance_sessions`
- `attendance_records`
- `attendance_scan_attempts`

Gunakan query sederhana per hari untuk deteksi gagal scan tinggi atau banyak `out_of_radius`.
