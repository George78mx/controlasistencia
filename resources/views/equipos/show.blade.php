@extends('layouts.app')
@section('title', $equipo->nombre)
@section('subtitle', ($equipo->ubicacion ? $equipo->ubicacion.' · ' : '').$equipo->baseUrl())
@section('actions')
    @can('equipos.sincronizar')
        <form method="POST" action="{{ route('equipos.probar', $equipo) }}" data-loading="Probando">@csrf<button class="btn btn-light" type="submit"><i class="bi bi-plug me-1"></i>Probar conexión</button></form>
        <form method="POST" action="{{ route('equipos.sincronizar', $equipo) }}" data-loading="Descargando">@csrf<button class="btn btn-primary" type="submit"><i class="bi bi-cloud-download me-1"></i>Descargar ahora</button></form>
    @endcan
    @can('equipos.gestionar')<a href="{{ route('equipos.edit', $equipo) }}" class="btn btn-light"><i class="bi bi-pencil"></i></a>@endcan
@endsection
@section('content')
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header">Estado
                @if(!$equipo->activo)<span class="badge-soft soft-secondary">Inactivo</span>
                @elseif($equipo->en_linea)<span class="badge-soft soft-success">En línea</span>
                @else<span class="badge-soft soft-danger">Sin conexión</span>@endif
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted-2 fw-normal">Modelo</dt><dd class="col-7">{{ $equipo->modelo ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">No. de serie</dt><dd class="col-7 mono text-break">{{ $equipo->numero_serie ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Firmware</dt><dd class="col-7">{{ $equipo->firmware ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">MAC</dt><dd class="col-7 mono">{{ $equipo->mac ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Usuario ISAPI</dt><dd class="col-7">{{ $equipo->usuario }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Consulta cada</dt><dd class="col-7">{{ $equipo->intervalo() }} minutos</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Última consulta</dt><dd class="col-7">{{ $equipo->ultima_sincronizacion_at?->format('d/m/Y H:i:s') ?? 'Nunca' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Próxima</dt><dd class="col-7">{{ $equipo->activo ? ($equipo->proximaSincronizacion()?->format('d/m/Y H:i') ?? 'En el siguiente minuto') : 'Desactivado' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Descargado hasta</dt><dd class="col-7">{{ $equipo->descargado_hasta?->format('d/m/Y H:i:s') ?? '—' }}</dd>
                </dl>
                @if($equipo->ultimo_error)
                    <div class="alert alert-danger small py-2 mt-3 mb-0 text-break">{{ $equipo->ultimo_error }}</div>
                @endif
            </div>
        </div>

        @can('equipos.sincronizar')
        <div class="card mb-3">
            <div class="card-header">Descargar un rango de fechas</div>
            <div class="card-body">
                <form method="POST" action="{{ route('equipos.sincronizar', $equipo) }}" data-loading="Descargando" class="row g-2">@csrf
                    <div class="col-6"><label class="form-label small">Desde</label><input type="date" name="desde" class="form-control" value="{{ now()->subDays(7)->toDateString() }}" required></div>
                    <div class="col-6"><label class="form-label small">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-calendar-range me-1"></i>Descargar rango</button></div>
                </form>
                <div class="form-text">Útil para recuperar checadas de días anteriores. No se duplican registros.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Usuarios registrados en la terminal</div>
            <div class="card-body">
                <p class="small text-muted-2">Consulta los usuarios dados de alta en el equipo para importarlos al catálogo de empleados.</p>
                <form method="POST" action="{{ route('equipos.usuarios', $equipo) }}" data-loading="Consultando">@csrf<button class="btn btn-light w-100" type="submit"><i class="bi bi-people me-1"></i>Consultar usuarios</button></form>
            </div>
        </div>
        @endcan

        @can('equipos.gestionar')
        <div class="card">
            <div class="card-header">Eventos en tiempo real (opcional)</div>
            <div class="card-body small">
                <p class="text-muted-2">En la web del equipo: <em>Configuración → Red → Servicio avanzado → HTTP Listening</em> (o <em>Servidor de escucha</em>), captura esta URL:</p>
                <input class="form-control form-control-sm mono" readonly value="{{ route('hikvision.push', $equipo->token_push) }}" onclick="this.select()">
                <p class="text-muted-2 mt-2 mb-2">La descarga periódica sigue activa para cubrir cualquier hueco.</p>
                <form method="POST" action="{{ route('equipos.token', $equipo) }}" data-confirm="¿Generar una nueva URL? La anterior dejará de funcionar.">@csrf<button class="btn btn-sm btn-link px-0">Generar nueva URL</button></form>
            </div>
        </div>
        @endcan
    </div>

    <div class="col-xl-8">
        @if($usuarios)
        <div class="card mb-3">
            <form method="POST" action="{{ route('equipos.importar', $equipo) }}">@csrf
            <div class="card-header">Usuarios en el equipo ({{ count($usuarios) }})
                <button class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i>Importar seleccionados</button>
            </div>
            <div class="table-responsive" style="max-height:420px">
                <table class="table table-hover">
                    <thead><tr><th><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.chk-u').forEach(c=>c.checked=this.checked)"></th><th>No. empleado</th><th>Nombre</th><th>Rostro</th><th>Huella</th><th>Tarjeta</th><th>Catálogo</th></tr></thead>
                    <tbody>
                    @foreach($usuarios as $u)
                        <tr>
                            <td>@unless($u['existe'])<input type="checkbox" class="form-check-input chk-u" name="usuarios[]" value="{{ $u['employeeNo'] }}|{{ $u['name'] }}">@endunless</td>
                            <td class="mono">{{ $u['employeeNo'] }}</td><td>{{ $u['name'] }}</td>
                            <td>{{ $u['numOfFace'] }}</td><td>{{ $u['numOfFP'] }}</td><td>{{ $u['numOfCard'] }}</td>
                            <td>{!! $u['existe'] ? '<span class="badge-soft soft-success">Registrado</span>' : '<span class="badge-soft soft-warning">Nuevo</span>' !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            </form>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header">Últimas checadas <a href="{{ route('checadas.index', ['device_id' => $equipo->id, 'desde' => now()->subDays(7)->toDateString()]) }}" class="small fw-normal">Ver todas</a></div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Fecha y hora</th><th>Empleado</th><th>Tipo</th><th>Método</th><th>Origen</th></tr></thead>
                    <tbody>
                    @forelse($checadas as $c)
                        <tr>
                            <td class="text-nowrap">{{ $c->fecha_hora->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $c->empleado?->nombre_completo ?? '' }} <span class="text-muted-2 mono">#{{ $c->numero_empleado }}</span></td>
                            <td>{{ $c->tipo_texto }}</td><td>{{ $c->metodo_texto }}</td>
                            <td><span class="badge-soft soft-{{ $c->origen === 'push' ? 'info' : 'secondary' }}">{{ $c->origen === 'push' ? 'Tiempo real' : 'Consulta' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Sin checadas descargadas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Historial de consultas</div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Estado</th><th>Rango consultado</th><th class="text-end">Leídos</th><th class="text-end">Nuevos</th><th>Detalle</th></tr></thead>
                    <tbody>
                    @forelse($logs as $l)
                        <tr>
                            <td class="text-nowrap">{{ $l->created_at->format('d/m H:i:s') }}</td>
                            <td class="text-capitalize">{{ $l->tipo }}</td>
                            <td><span class="badge-soft soft-{{ $l->estado === 'ok' ? 'success' : ($l->estado === 'error' ? 'danger' : 'secondary') }}">{{ strtoupper($l->estado) }}</span></td>
                            <td class="small text-nowrap">{{ $l->rango_desde?->format('d/m H:i') }}{{ $l->rango_hasta ? ' – '.$l->rango_hasta->format('d/m H:i') : '' }}</td>
                            <td class="text-end">{{ $l->eventos_recibidos }}</td>
                            <td class="text-end fw-semibold">{{ $l->eventos_nuevos }}</td>
                            <td class="small text-break" style="max-width:280px">{{ Str::limit($l->mensaje, 160) }} <span class="text-muted-2">({{ number_format($l->duracion_ms / 1000, 1) }} s)</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">Sin consultas registradas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())<div class="card-footer bg-transparent">{{ $logs->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
