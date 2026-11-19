@extends('layouts.app')
@section('title', $empleado->nombre_completo)
@section('subtitle', 'No. '.$empleado->numero_empleado.($empleado->puesto ? ' · '.$empleado->puesto->nombre : ''))
@section('actions')
    @can('reportes.ver')<a href="{{ route('reportes.ver', ['kardex', 'empleado_id' => $empleado->id, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}" class="btn btn-light"><i class="bi bi-person-vcard me-1"></i>Kárdex</a>@endcan
    @can('incidencias.gestionar')<a href="{{ route('incidencias.create', ['empleado_id' => $empleado->id]) }}" class="btn btn-light"><i class="bi bi-clipboard-plus me-1"></i>Incidencia</a>@endcan
    @can('empleados.gestionar')<a href="{{ route('empleados.edit', $empleado) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Editar</a>@endcan
    @can('empleados.eliminar')
        <form method="POST" action="{{ route('empleados.destroy', $empleado) }}" data-confirm="¿Eliminar este empleado? Se conservará su historial de asistencia.">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
    @endcan
@endsection
@section('content')
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                @include('partials.avatar', ['e' => $empleado, 'clase' => 'avatar-lg mb-3'])
                <h5 class="fw-bold mb-0">{{ $empleado->nombre_completo }}</h5>
                <div class="text-muted-2">{{ $empleado->departamento?->nombre ?? 'Sin departamento' }}</div>
                <span class="badge-soft soft-{{ $empleado->activo ? 'success' : 'secondary' }} mt-2 d-inline-block">{{ $empleado->activo ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <div class="card-body border-top">
                <dl class="row small mb-0">
                    @foreach([
                        'RFC' => $empleado->rfc, 'CURP' => $empleado->curp, 'NSS' => $empleado->nss,
                        'Nacimiento' => $empleado->fecha_nacimiento?->format('d/m/Y'), 'Correo' => $empleado->email, 'Teléfono' => $empleado->telefono,
                        'Dirección' => $empleado->direccion, 'Ingreso' => $empleado->fecha_ingreso?->format('d/m/Y'), 'Baja' => $empleado->fecha_baja?->format('d/m/Y'),
                        'Equipo' => $empleado->device?->nombre, 'Emergencia' => trim($empleado->contacto_emergencia.' '.$empleado->telefono_emergencia),
                    ] as $k => $val)
                        <dt class="col-4 text-muted-2 fw-normal">{{ $k }}</dt><dd class="col-8 text-break">{{ $val ?: '—' }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Horario</div>
            <div class="card-body small">
                @if($empleado->horario)
                    <div class="fw-semibold mb-2">{{ $empleado->horario->nombre }}</div>
                    @foreach($empleado->horario->dias as $d)
                        <div class="d-flex justify-content-between border-bottom py-1"><span>{{ \App\Models\Horario::DIAS[$d->dia_semana] }}</span>
                            <span class="{{ $d->laborable ? '' : 'text-muted-2' }}">{{ $d->laborable ? substr($d->entrada,0,5).' – '.substr($d->salida,0,5) : 'Descanso' }}</span></div>
                    @endforeach
                    <div class="mt-2 text-muted-2">Tolerancia {{ $empleado->horario->tolerancia_minutos }} min · Falta a partir de {{ $empleado->horario->minutos_para_falta }} min de retardo</div>
                @else
                    <div class="alert alert-warning mb-0 small">Sin horario asignado: no se calculan retardos ni faltas.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <form class="filters row g-2 align-items-end">
            <div class="col-5"><label class="form-label">Desde</label><input type="date" name="desde" value="{{ $desde->toDateString() }}" class="form-control"></div>
            <div class="col-5"><label class="form-label">Hasta</label><input type="date" name="hasta" value="{{ $hasta->toDateString() }}" class="form-control"></div>
            <div class="col-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
        </form>
        <div class="row g-2 mb-3">
            @foreach(['asistencia', 'retardo', 'falta', 'justificada'] as $e)
                <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body py-3">
                    <div class="valor">{{ $resumen[$e] ?? 0 }}</div><div class="etiqueta">{{ \App\Models\Asistencia::ESTATUS[$e][0] }}s</div>
                </div></div></div>
            @endforeach
        </div>
        <div class="card mb-3">
            <div class="card-header">Asistencia del periodo</div>
            <div class="table-responsive" style="max-height:520px">
                <table class="table table-hover">
                    <thead><tr><th>Fecha</th><th>Programado</th><th>Entrada</th><th>Salida</th><th>Retardo</th><th>Horas</th><th>Estatus</th></tr></thead>
                    <tbody>
                    @forelse($asistencias as $a)
                        <tr>
                            <td class="text-nowrap">{{ $a->fecha->translatedFormat('D d/m/Y') }}</td>
                            <td class="small text-muted-2 text-nowrap">{{ $a->entrada_programada ? $a->entrada_programada->format('H:i').' – '.$a->salida_programada->format('H:i') : '—' }}</td>
                            <td>{{ $a->entrada?->format('H:i') ?? '—' }}</td>
                            <td>{{ $a->salida?->format('H:i') ?? '—' }}</td>
                            <td>{{ $a->minutos_retardo ? $a->minutos_retardo.' min' : '' }}</td>
                            <td>{{ $a->minutos_trabajados ? \App\Models\Asistencia::minutosAHoras($a->minutos_trabajados) : '' }}</td>
                            <td>@include('partials.estatus', ['a' => $a]) @if($a->observaciones && $a->estatus !== 'justificada')<div class="small text-muted-2">{{ $a->observaciones }}</div>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty"><i class="bi bi-calendar"></i>Sin registros en el periodo.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100"><div class="card-header">Últimas checadas</div>
                    <div class="card-body p-0">
                        @forelse($checadas as $c)
                            <div class="d-flex justify-content-between px-3 py-2 border-bottom small"><span>{{ $c->fecha_hora->format('d/m/Y H:i:s') }}</span><span class="text-muted-2">{{ $c->device?->nombre }}</span></div>
                        @empty <div class="empty">Sin checadas.</div> @endforelse
                    </div></div>
            </div>
            <div class="col-md-6">
                <div class="card h-100"><div class="card-header">Incidencias</div>
                    <div class="card-body p-0">
                        @forelse($incidencias as $i)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom small">
                                <span>{{ $i->fecha_inicio->format('d/m/Y') }} – {{ $i->fecha_fin->format('d/m/Y') }}</span>
                                <span class="badge-soft" style="background:{{ $i->tipo->color }}1f;color:{{ $i->tipo->color }}">{{ $i->tipo->nombre }}</span></div>
                        @empty <div class="empty">Sin incidencias.</div> @endforelse
                    </div></div>
            </div>
        </div>
    </div>
</div>
@endsection
