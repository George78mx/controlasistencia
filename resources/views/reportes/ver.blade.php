@extends('layouts.app')
@section('title', $titulo)
@section('subtitle', 'Periodo '.$periodo.($filtroTexto ? ' · '.$filtroTexto : ''))
@section('actions')
    <a href="{{ route('reportes.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
    @can('reportes.exportar')
        <a href="{{ request()->fullUrlWithQuery(['formato' => 'pdf']) }}" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="{{ request()->fullUrlWithQuery(['formato' => 'excel']) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
    @endcan
@endsection
@section('content')
<form class="filters row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="desde" value="{{ $filtros['desde']->toDateString() }}" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="hasta" value="{{ $filtros['hasta']->toDateString() }}" class="form-control"></div>
    @if($tipo === 'kardex')
        <div class="col-md-6"><label class="form-label">Empleado *</label>
            <select name="empleado_id" class="form-select" required><option value="">Selecciona…</option>
                @foreach($empleados as $e)<option value="{{ $e->id }}" @selected($filtros['empleado_id'] == $e->id)>{{ $e->nombre_completo }} (#{{ $e->numero_empleado }}){{ $e->trashed() ? ' — eliminado' : '' }}</option>@endforeach</select></div>
    @else
        <div class="col-md-2"><label class="form-label">Departamento</label>
            <select name="departamento_id" class="form-select"><option value="">Todos</option>
                @foreach($departamentos as $d)<option value="{{ $d->id }}" @selected($filtros['departamento_id'] == $d->id)>{{ $d->nombre }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Equipo</label>
            <select name="device_id" class="form-select"><option value="">Todos</option>
                @foreach($equipos as $d)<option value="{{ $d->id }}" @selected($filtros['device_id'] == $d->id)>{{ $d->nombre }}</option>@endforeach</select></div>
        @if($tipo === 'incidencias')
            <div class="col-md-2"><label class="form-label">Tipo</label>
                <select name="tipo_incidencia_id" class="form-select"><option value="">Todos</option>
                    @foreach($tiposIncidencia as $t)<option value="{{ $t->id }}" @selected($filtros['tipo_incidencia_id'] == $t->id)>{{ $t->nombre }}</option>@endforeach</select></div>
        @else
            <div class="col-md-2"><label class="form-label">Empleado</label>
                <select name="empleado_id" class="form-select"><option value="">Todos</option>
                    @foreach($empleados as $e)<option value="{{ $e->id }}" @selected($filtros['empleado_id'] == $e->id)>{{ $e->nombre_completo }}</option>@endforeach</select></div>
        @endif
    @endif
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Generar</button></div>
</form>

@if($tipo === 'kardex' && $empleado)
    <div class="card mb-3"><div class="card-body d-flex flex-wrap align-items-center gap-4">
        <div class="d-flex align-items-center gap-3">@include('partials.avatar', ['e' => $empleado])
            <div><div class="fw-bold">{{ $empleado->nombre_completo }}</div><div class="small text-muted-2">#{{ $empleado->numero_empleado }} · {{ $empleado->departamento?->nombre }} · {{ $empleado->horario?->nombre ?? 'Sin horario' }}</div></div></div>
        @foreach($resumen as $k => $val)
            <div class="text-center"><div class="fw-bold fs-5">{{ $val }}</div><div class="small text-muted-2">{{ $k }}</div></div>
        @endforeach
    </div></div>
@endif

@if($tipo === 'general' && $retardosPorFalta > 0)
    <div class="small text-muted-2 mb-2"><i class="bi bi-info-circle me-1"></i>Cada {{ $retardosPorFalta }} retardos equivalen a 1 falta (columna «Faltas x retardos»). Se cambia en Configuración.</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle reporte">
            @include('reportes.tablas.'.$tipo)
        </table>
    </div>
</div>
@push('head')
<style>.reporte td.num, .reporte th { text-align: left } .reporte td.num { text-align: right } .reporte tr.total td { background:#f8fafc } .reporte td { white-space: nowrap }
.estatus-falta{color:#dc2626;font-weight:600}.estatus-retardo{color:#d97706;font-weight:600}.estatus-justificada{color:#0891b2}.estatus-asistencia{color:#059669}</style>
@endpush
@endsection
