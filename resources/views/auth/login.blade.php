<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=1" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
    <section class="login-hero">
        <div class="d-flex align-items-center gap-2 fw-bold"><span class="avatar" style="background:rgba(255,255,255,.15);color:#fff"><i class="bi bi-person-badge"></i></span> {{ \App\Models\Setting::get('empresa_nombre') }}</div>
        <div>
            <h2>Asistencia clara,<br>incidencias bajo control.</h2>
            <p class="text-white-50 mt-3 mb-0" style="max-width:440px">Checadas descargadas automáticamente de tus terminales faciales Hikvision, con retardos, faltas y reportes listos para exportar.</p>
        </div>
        <div class="d-flex gap-4 small text-white-50">
            <span><i class="bi bi-hdd-network me-1"></i>Equipos por VPN</span>
            <span><i class="bi bi-file-earmark-pdf me-1"></i>PDF y Excel</span>
            <span><i class="bi bi-shield-lock me-1"></i>Roles y permisos</span>
        </div>
    </section>
    <section class="login-form">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <h3 class="fw-bold mb-1">Bienvenido</h3>
            <p class="text-muted-2 mb-4">Ingresa con tu correo y contraseña.</p>
            @error('email')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control form-control-lg" required></div>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Mantener sesión iniciada</label>
            </div>
            <button class="btn btn-primary btn-lg w-100">Entrar <i class="bi bi-arrow-right ms-1"></i></button>
        </form>
    </section>
</div>
</body>
</html>
