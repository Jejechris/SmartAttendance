<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login SmartAttendance</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; min-height: 100vh; display: grid; place-items: center; }
        .card { width: 100%; max-width: 420px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
        h1 { margin-top: 0; font-size: 20px; color: #0f172a; }
        p { color: #475569; font-size: 14px; }
        label { display: block; margin-bottom: 6px; color: #334155; font-size: 13px; }
        input { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 12px; }
        button { width: 100%; border: none; background: #0f766e; color: #fff; border-radius: 8px; padding: 10px; cursor: pointer; }
        .error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 8px; margin-bottom: 10px; font-size: 13px; }
        .hint { margin-top: 10px; font-size: 12px; color: #64748b; }
        .remember { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .remember input { width: auto; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Login SmartAttendance</h1>
        <p>Gunakan akun guru atau siswa yang sudah terdaftar.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <label>Email</label>
            <input type="email" name="email" required value="{{ old('email') }}">

            <label>Password</label>
            <input type="password" name="password" required>

            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                Ingat saya
            </label>

            <button type="submit">Masuk</button>
        </form>

        <p class="hint">
            Demo guru: <code>guru.absen@demo.sch.id</code> / <code>password</code>
        </p>
    </div>
</body>
</html>
