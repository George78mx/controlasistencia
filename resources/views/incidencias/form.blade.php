@extends('layouts.app')
@section('title', $incidencia->exists ? 'Editar incidencia' : 'Nueva incidencia')
@section('content')
@php $v = fn($c) => old($c, $incidencia->$c instanceof \Carbon\Carbon ? $incidencia->$c->toDateString() : $incidencia->$c); @endphp
<div class="card" style="max-width:820px">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" action="{{ $incidencia->exists ? route('incidencias.update', $incidencia) : route('incidencias.store') }}" class="row g-3">
            @csrf @if($incidencia->exists) @method('PUT') @endif
            <div class="col-md-7"><label class="form-label">Empleado *</label>
                <select name="empleado_id" class="form-select" required><option value="">Selecciona…</option>
                    @foreach($empleados as $e)<option value="{{ $e->id }}" @selected($v('empleado_id') == $e->id)>{{ $e->nombre_completo }} (#{{ $e->numero_empleado }})</option>@endforeach</select></div>
            <div class="col-md-5"><label class="form-label">Tipo *</label>
                <select name="tipo_incidencia_id" class="form-select" required><option value="">Selecciona…</option>
                    @foreach($tipos as $t)<option value="{{ $t->id }}" @selected($v('tipo_incidencia_id') == $t->id)>{{ $t->nombre }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Desde *</label><input type="date" name="fecha_inicio" value="{{ $v('fecha_inicio') }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Hasta *</label><input type="date" name="fecha_fin" value="{{ $v('fecha_fin') }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Folio</label><input name="folio" value="{{ $v('folio') }}" class="form-control" placeholder="Ej. folio de incapacidad"></div>
            <div class="col-12"><label class="form-label">Motivo / observaciones</label><textarea name="motivo" rows="3" class="form-control">{{ $v('motivo') }}</textarea></div>
            <div class="col-12"><label class="form-label">Documento (PDF o imagen, máx. 5 MB)</label><input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                @if($incidencia->documento)<div class="form-text"><a href="{{ route('incidencias.documento', $incidencia) }}">Ver documento actual</a></div>@endif</div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="{{ route('incidencias.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
