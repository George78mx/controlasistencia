@extends('layouts.app')
@section('title', 'Tablero')
@section('subtitle', now()->translatedFormat('l d \d\e F \d\e Y'))
@section('actions')
    @can('reportes.ver')<a href="{{ route('reportes.index') }}" class="btn btn-primary"><i class="bi bi-bar-chart-line me-1"></i>Reportes</a>@endcan
@endsection
@section('content')
@php
    $presentes = ($conteoHoy['asistencia'] ?? 0) + ($conteoHoy['retardo'] ?? 0);
    $tarjetas = [
        ['Empleados activos', $totalEmpleados, 'people', 'primary', route('empleados.index')],
        ['Presentes hoy', $presentes, 'person-check', 'success', route('asistencia.index')],
        ['Retardos hoy', $conteoHoy['retardo'] ?? 0, 'alarm', 'warning', route('asistencia.index', ['estatus' => 'retardo'])],
        ['Faltas hoy', $conteoHoy['falta'] ?? 0, 'person-x', 'danger', route('asistencia.index', ['estatus' => 'falta'])],
        ['Justificados hoy', $conteoHoy['justificada'] ?? 0, 'clipboard2-check', 'info', route('asistencia.index', ['estatus' => 'justificada'])],
        ['Aún sin checar', $conteoHoy['pendiente'] ?? 0, 'hourglass-split', 'secondary', route('asistencia.index', ['estatus' => 'pendiente'])],
    ];
@endphp

@if($sinHorario || $noRegistrados)
<div class="d-flex flex-column gap-2 mb-3">
    @if($sinHorario)
        <div class="alert alert-warning mb-0 py-2"><i class="bi bi-exclamation-circle me-2"></i><strong>{{ $sinHorario }}</strong> empleado(s) activos no tienen horario asignado; no se les pueden calcular retardos ni faltas. <a href="{{ route('empleados.index') }}">Revisar</a></div>
    @endif
    @if($noRegistrados)
        <div class="alert alert-info mb-0 py-2"><i class="bi bi-info-circle me-2"></i>Hay checadas de <strong>{{ $noRegistrados }}</strong> número(s) de empleado que no existen en el catálogo. <a href="{{ route('checadas.index', ['sin_empleado' => 1, 'desde' => now()->subDays(30)->toDateString()]) }}">Ver checadas</a></div>
    @endif
</div>
@endif

<div class="row g-3 mb-3">
    @foreach($tarjetas as [$t, $v, $i, $c, $url])
    <div class="col-6 col-md-4 col-xl-2">
        <a href="{{ $url }}" class="text-decoration-none text-reset">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="icon soft-{{ $c }} mb-3"><i class="bi bi-{{ $i }}"></i></div>
                <div class="valor">{{ $v }}</div>
                <div class="etiqueta">{{ $t }}</div>
            </div></div>
        </a>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">Asistencia de los últimos 14 días</div>
            <div class="card-body"><canvas id="grafica" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Equipos <a href="{{ route('equipos.index') }}" class="small fw-normal">Ver todos</a></div>
            <div class="card-body p-0">
                @forelse($equipos as $eq)
                    <a href="{{ route('equipos.show', $eq) }}" class="d-flex align-items-center gap-3 px-3 py-3 border-bottom text-reset text-decoration-none">
                        <span class="status-dot {{ !$eq->activo ? 'idle' : ($eq->en_linea ? 'on' : 'off') }}"></span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $eq->nombre }}</div>
                            <div class="small text-muted-2">{{ $eq->ip }} · {{ $eq->ubicacion ?: 'Sin ubicación' }}</div>
                        </div>
                        <div class="text-end small text-muted-2">
                            {{ $eq->ultima_sincronizacion_at ? $eq->ultima_sincronizacion_at->diffForHumans() : 'Nunca' }}
                            <div>cada {{ $eq->intervalo() }} min</div>
                        </div>
                    </a>
                @empty
                    <div class="empty"><i class="bi bi-hdd-network"></i>Aún no hay equipos registrados.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Últimas checadas</div>
            <div class="card-body p-0">
                @forelse($ultimasChecadas as $c)
                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                        @include('partials.avatar', ['e' => $c->empleado])
                        <div class="flex-grow-1 lh-sm">
                            <div class="fw-semibold small">{{ $c->empleado?->nombre_completo ?? 'No. '.$c->numero_empleado }}</div>
                            <div class="small text-muted-2">{{ $c->device?->nombre }} · {{ $c->tipo_texto }}</div>
                        </div>
                        <div class="small text-end"><div class="fw-semibold">{{ $c->fecha_hora->format('H:i') }}</div><div class="text-muted-2">{{ $c->fecha_hora->format('d/m') }}</div></div>
                    </div>
                @empty
                    <div class="empty"><i class="bi bi-fingerprint"></i>Sin checadas todavía.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Retardos de hoy</div>
            <div class="card-body p-0">
                @forelse($retardosHoy as $a)
                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                        @include('partials.avatar', ['e' => $a->empleado])
                        <div class="flex-grow-1 small fw-semibold">{{ $a->empleado?->nombre_completo }}<div class="text-muted-2 fw-normal">Entrada {{ $a->entrada?->format('H:i') }}</div></div>
                        <span class="badge-soft soft-warning">+{{ $a->minutos_retardo }} min</span>
                    </div>
                @empty
                    <div class="empty"><i class="bi bi-emoji-smile"></i>Sin retardos hoy.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Incidencias vigentes</div>
            <div class="card-body p-0">
                @forelse($incidenciasActivas as $i)
                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                        @include('partials.avatar', ['e' => $i->empleado])
                        <div class="flex-grow-1 small fw-semibold">{{ $i->empleado?->nombre_completo }}<div class="text-muted-2 fw-normal">{{ $i->fecha_inicio->format('d/m') }} – {{ $i->fecha_fin->format('d/m') }}</div></div>
                        <span class="badge-soft" style="background:{{ $i->tipo->color }}1f;color:{{ $i->tipo->color }}">{{ $i->tipo->nombre }}</span>
                    </div>
                @empty
                    <div class="empty"><i class="bi bi-clipboard2"></i>Sin incidencias vigentes.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const g = @json($grafica);
new Chart(document.getElementById('grafica'), {
    type: 'bar',
    data: { labels: g.labels, datasets: [
        { label: 'Asistencia', data: g.asistencia, backgroundColor: '#10b981', borderRadius: 6 },
        { label: 'Retardo', data: g.retardo, backgroundColor: '#f59e0b', borderRadius: 6 },
        { label: 'Falta', data: g.falta, backgroundColor: '#ef4444', borderRadius: 6 },
        { label: 'Justificada', data: g.justificada, backgroundColor: '#06b6d4', borderRadius: 6 },
    ]},
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
@endpush
