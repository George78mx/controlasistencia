@extends('layouts.app')
@section('title', 'Asistencia del día')
@section('subtitle', ucfirst($fecha->translatedFormat('l d \d\e F \d\e Y')))
@section('actions')
    <a href="{{ route('asistencia.index', ['fecha' => $fecha->copy()->subDay()->toDateString()] + request()->except('fecha')) }}" class="btn btn-light"><i class="bi bi-chevron-left"></i></a>
    <a href="{{ route('asistencia.index', request()->except('fecha')) }}" class="btn btn-light">Hoy</a>
    <a href="{{ route('asistencia.index', ['fecha' => $fecha->copy()->addDay()->toDateString()] + request()->except('fecha')) }}" class="btn btn-light"><i class="bi bi-chevron-right"></i></a>
    @can('asistencia.recalcular')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recalcular"><i class="bi bi-arrow-repeat me-1"></i>Recalcular</button>@endcan
@endsection
@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('asistencia.index', ['fecha' => $fecha->toDateString()]) }}" class="badge-estatus soft-{{ request('estatus') ? 'light' : 'primary' }} text-decoration-none fs-6">Todos {{ $conteo->sum() }}</a>
    @foreach(\App\Models\Asistencia::ESTATUS as $k => [$t, $c])
        @if($conteo[$k] ?? 0)
            <a href="{{ route('asistencia.index', ['fecha' => $fecha->toDateString(), 'estatus' => $k]) }}" class="badge-estatus soft-{{ $c }} text-decoration-none fs-6 {{ request('estatus') === $k ? 'border border-2' : '' }}">{{ $t }} {{ $conteo[$k] }}</a>
        @endif
    @endforeach
</div>
<form class="filters row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Departamento</label>
        <select name="departamento_id" class="form-select"><option value="">Todos</option>
            @foreach($departamentos as $d)<option value="{{ $d->id }}" @selected(request('departamento_id') == $d->id)>{{ $d->nombre }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Estatus</label>
        <select name="estatus" class="form-select"><option value="">Todos</option>
            @foreach(\App\Models\Asistencia::ESTATUS as $k => [$t])<option value="{{ $k }}" @selected(request('estatus') === $k)>{{ $t }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Empleado</label><input name="q" value="{{ request('q') }}" class="form-control"></div>
    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
</form>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Empleado</th><th>Departamento</th><th>Programado</th><th>Entrada</th><th>Salida</th><th>Retardo</th><th>Horas</th><th>Checadas</th><th>Estatus</th><th></th></tr></thead>
            <tbody>
            @forelse($registros as $a)
                <tr>
                    <td><a href="{{ route('empleados.show', $a->empleado) }}" class="d-flex align-items-center gap-2 text-reset text-decoration-none">@include('partials.avatar', ['e' => $a->empleado])<span class="fw-semibold">{{ $a->empleado->nombre_completo }}<br><span class="mono small text-muted-2 fw-normal">#{{ $a->empleado->numero_empleado }}</span></span></a></td>
                    <td>{{ $a->empleado->departamento?->nombre }}</td>
                    <td class="small text-muted-2 text-nowrap">{{ $a->entrada_programada ? $a->entrada_programada->format('H:i').' – '.$a->salida_programada->format('H:i') : '—' }}</td>
                    <td class="fw-semibold">{{ $a->entrada?->format('H:i') ?? '—' }}</td>
                    <td>{{ $a->salida?->format('H:i') ?? '—' }}</td>
                    <td>{!! $a->minutos_retardo ? '<span class="text-warning fw-semibold">'.$a->minutos_retardo.' min</span>' : '' !!}</td>
                    <td>{{ $a->minutos_trabajados ? \App\Models\Asistencia::minutosAHoras($a->minutos_trabajados) : '' }}</td>
                    <td>{{ $a->num_checadas }}</td>
                    <td>@include('partials.estatus', ['a' => $a]) @if($a->observaciones && $a->estatus !== 'justificada')<div class="small text-muted-2">{{ $a->observaciones }}</div>@endif</td>
                    <td class="text-end">@if(in_array($a->estatus, ['falta', 'retardo']) && auth()->user()->can('incidencias.gestionar'))
                        <a href="{{ route('incidencias.create', ['empleado_id' => $a->empleado_id, 'fecha' => $a->fecha->toDateString()]) }}" class="btn btn-sm btn-light" title="Justificar"><i class="bi bi-clipboard-plus"></i></a>@endif</td>
                </tr>
            @empty
                <tr><td colspan="10"><div class="empty"><i class="bi bi-calendar-x"></i>Sin registros para este día. Revisa que los empleados tengan horario y usa <strong>Recalcular</strong> si es necesario.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('asistencia.recalcular')
<div class="modal fade" id="recalcular" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('asistencia.recalcular') }}" data-loading="Recalculando">@csrf
        <div class="modal-header"><h5 class="modal-title">Recalcular asistencia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <p class="small text-muted-2 mb-0">Vuelve a calcular retardos, faltas y justificaciones con las checadas, horarios e incidencias actuales.</p>
            <div class="col-6"><label class="form-label">Desde</label><input type="date" name="desde" value="{{ $fecha->toDateString() }}" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Hasta</label><input type="date" name="hasta" value="{{ $fecha->toDateString() }}" class="form-control" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Recalcular</button></div>
    </form>
</div></div></div>
@endcan
@endsection
