@extends('layouts.app')
@section('title', 'Incidencias')
@section('subtitle', 'Vacaciones, incapacidades, permisos y justificaciones')
@section('actions')
    @can('incidencias.gestionar')<a href="{{ route('incidencias.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva incidencia</a>@endcan
@endsection
@section('content')
<form class="filters row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Empleado</label><input name="q" value="{{ request('q') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Tipo</label>
        <select name="tipo_incidencia_id" class="form-select"><option value="">Todos</option>
            @foreach($tipos as $t)<option value="{{ $t->id }}" @selected(request('tipo_incidencia_id') == $t->id)>{{ $t->nombre }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="desde" value="{{ request('desde') }}" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button></div>
</form>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Empleado</th><th>Tipo</th><th>Periodo</th><th>Días</th><th>Folio</th><th>Motivo</th><th>Registró</th><th></th></tr></thead>
            <tbody>
            @forelse($incidencias as $i)
                <tr>
                    <td class="fw-semibold">{{ $i->empleado?->nombre_completo }}<br><span class="mono small text-muted-2 fw-normal">#{{ $i->empleado?->numero_empleado }}</span></td>
                    <td><span class="badge-soft" style="background:{{ $i->tipo->color }}1f;color:{{ $i->tipo->color }}">{{ $i->tipo->nombre }}</span></td>
                    <td class="text-nowrap">{{ $i->fecha_inicio->format('d/m/Y') }} – {{ $i->fecha_fin->format('d/m/Y') }}</td>
                    <td>{{ $i->dias }}</td>
                    <td>{{ $i->folio }}</td>
                    <td class="small" style="max-width:260px">{{ Str::limit($i->motivo, 90) }}</td>
                    <td class="small text-muted-2">{{ $i->usuario?->name }}</td>
                    <td class="text-end text-nowrap">
                        @if($i->documento)<a href="{{ route('incidencias.documento', $i) }}" class="btn btn-sm btn-light" title="Documento"><i class="bi bi-paperclip"></i></a>@endif
                        @can('incidencias.gestionar')
                            <a href="{{ route('incidencias.edit', $i) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('incidencias.destroy', $i) }}" class="d-inline" data-confirm="¿Eliminar la incidencia? Se recalculará la asistencia de esos días.">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty"><i class="bi bi-clipboard2"></i>Sin incidencias registradas.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($incidencias->hasPages())<div class="card-footer bg-transparent">{{ $incidencias->links() }}</div>@endif
</div>
@endsection
