# API Contract Ringkas - Absensi QR

Semua endpoint memakai session auth Laravel (`auth`).

## 1) Buat sesi

`POST /teacher/attendance/sessions`

Body:

```json
{
  "class_id": 10,
  "subject_id": 20,
  "started_at": "2026-02-09 07:30:00",
  "ended_at": "2026-02-09 09:00:00",
  "late_tolerance_minutes": 10,
  "qr_dynamic": true,
  "qr_rotate_seconds": 30,
  "location_validation": true,
  "center_lat": -6.2000000,
  "center_lng": 106.8166667,
  "radius_meters": 80
}
```

## 2) Buka sesi

`POST /teacher/attendance/sessions/{session}/open`

## 3) Ambil QR saat ini

`GET /teacher/attendance/sessions/{session}/qr`

Response:

```json
{
  "data": {
    "session_id": 123,
    "token": "...",
    "slot": 12345678,
    "expires_at": "2026-02-09T01:30:30Z",
    "scan_url": "https://host/attendance/scan?sid=123&t=..."
  }
}
```

## 4) Check-in siswa

`POST /attendance/check-in`

Body:

```json
{
  "session_id": 123,
  "token": "...",
  "lat": -6.2001234,
  "lng": 106.8169876
}
```

## 5) Realtime rekap

`GET /teacher/attendance/sessions/{session}/realtime`

## 6) Tutup sesi

`POST /teacher/attendance/sessions/{session}/close`

## 7) Export

- `GET /teacher/attendance/sessions/{session}/export?format=xlsx`
- `GET /teacher/attendance/sessions/{session}/export?format=pdf`
