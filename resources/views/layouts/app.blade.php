<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=1" rel="stylesheet">
    @stack('head')
</head>
<body>
@php
    $menu = [
        ['Principal', [
            ['dashboard', 'Tablero', 'grid-1x2', 'dashboard.ver', 'dashboard'],
            ['asistencia.index', 'Asistencia del día', 'calendar-check', 'asistencia.ver', 'asistencia.*'],
            ['checadas.index', 'Checadas', 'fingerprint', 'checadas.ver', 'checadas.*'],
            ['incidencias.index', 'Incidencias', 'clipboard2-pulse', 'incidencias.ver', 'incidencias.*'],
            ['reportes.index', 'Reportes', 'bar-chart-line', 'reportes.ver', 'reportes.*'],
        ]],
        ['Catálogos', [
            ['empleados.index', 'Empleados', 'people', 'empleados.ver', 'empleados.*'],
            ['equipos.index', 'Equipos', 'hdd-network', 'equipos.ver', 'equipos.*'],
            ['horarios.index', 'Horarios', 'clock-history', 'catalogos.gestionar', 'horarios.*'],
            [['catalogos.index', 'departamentos'], 'Departamentos', 'diagram-3', 'catalogos.gestionar', 'catalogos/departamentos'],
            [['catalogos.index', 'puestos'], 'Puestos', 'briefcase', 'catalogos.gestionar', 'catalogos/puestos'],
            [['catalogos.index', 'tipos-incidencia'], 'Tipos de incidencia', 'tags', 'catalogos.gestionar', 'catalogos/tipos-incidencia'],
            [['catalogos.index', 'festivos'], 'Días festivos', 'calendar-heart', 'catalogos.gestionar', 'catalogos/festivos'],
        ]],
        ['Administración', [
            ['usuarios.index', 'Usuarios', 'person-gear', 'usuarios.gestionar', 'usuarios.*'],
            ['roles.index', 'Roles y permisos', 'shield-lock', 'roles.gestionar', 'roles.*'],
            ['configuracion.edit', 'Configuración', 'sliders', 'configuracion.gestionar', 'configuracion.*'],
        ]],
    ];
@endphp
<aside class="sidebar" id="sidebar">
    <a href="{{ route('dashboard') }}" class="brand">
        <span class="logo"><i class="bi bi-person-badge"></i></span>
        <span class="fw-bold">Control de Asistencia<small>{{ \App\Models\Setting::get('empresa_nombre') }}</small></span>
    </a>
    <nav>
        @foreach($menu as [$seccion, $items])
            @php $visibles = collect($items)->filter(fn($i) => auth()->user()->can($i[3])); @endphp
            @if($visibles->isNotEmpty())
                <div class="nav-section">{{ $seccion }}</div>
                @foreach($visibles as [$ruta, $texto, $icono, $permiso, $patron])
                    @php
                        $url = is_array($ruta) ? route($ruta[0], $ruta[1]) : route($ruta);
                        $activo = str_contains($patron, '/') ? request()->is($patron.'*') : request()->routeIs($patron);
                    @endphp
                    <a href="{{ $url }}" class="nav-link {{ $activo ? 'active' : '' }}"><i class="bi bi-{{ $icono }}"></i>{{ $texto }}</a>
                @endforeach
            @endif
        @endforeach
    </nav>
    <div class="user-box">
        <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span>
        <div class="flex-grow-1 small lh-sm overflow-hidden">
            <a href="{{ route('perfil') }}" class="text-white text-decoration-none fw-semibold d-block text-truncate">{{ auth()->user()->name }}</a>
            <span class="text-secondary">{{ auth()->user()->getRoleNames()->implode(', ') ?: 'Sin rol' }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="btn btn-sm btn-link text-secondary" title="Cerrar sesión"><i class="bi bi-box-arrow-right fs-5"></i></button>
        </form>
    </div>
</aside>
<div class="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.remove('show')"></div>

<div class="main">
    <header class="topbar">
        <button class="btn btn-light d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
        <div class="flex-grow-1">
            <h1>@yield('title', 'Inicio')</h1>
            @hasSection('subtitle')<div class="text-muted-2 small">@yield('subtitle')</div>@endif
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">@yield('actions')</div>
    </header>
    <main class="content">
        @if(session('ok'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('ok') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger shadow-sm">
                <i class="bi bi-exclamation-triangle me-2"></i>Revisa los datos capturados:
                <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="px-4 py-3 small text-muted-2">© {{ date('Y') }} {{ \App\Models\Setting::get('empresa_nombre') }} · Control de Asistencia</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
document.querySelectorAll('form[data-loading]').forEach(f => f.addEventListener('submit', () => {
    const b = f.querySelector('[type=submit]'); if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + f.dataset.loading; }
}));
</script>
@stack('scripts')
</body>
</html>
