@extends('layouts.app')
@section('title', 'Empleados')
@section('subtitle', 'Catálogo de personal. El No. de empleado debe ser igual al registrado en la terminal.')
@section('actions')
    @can('empleados.gestionar')<a href="{{ route('empleados.importar') }}" class="btn btn-light"><i class="bi bi-file-earmark-arrow-up me-1"></i>Importar</a>@endcan
    @can('empleados.gestionar')<a href="{{ route('empleados.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Nuevo empleado</a>@endcan
@endsection
@section('content')
<form class="filters row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label">Buscar</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Nombre, número, RFC o CURP"></div>
    <div class="col-md-3"><label class="form-label">Departamento</label>
        <select name="departamento_id" class="form-select"><option value="">Todos</option>
            @foreach($departamentos as $d)<option value="{{ $d->id }}" @selected(request('departamento_id') == $d->id)>{{ $d->nombre }}</option>@endforeach
        </select></div>
    <div class="col-md-2"><label class="form-label">Equipo</label>
        <select name="device_id" class="form-select"><option value="">Todos</option>
            @foreach($equipos as $d)<option value="{{ $d->id }}" @selected(request('device_id') == $d->id)>{{ $d->nombre }}</option>@endforeach
        </select></div>
    <div class="col-md-2"><label class="form-label">Estado</label>
        <select name="estado" class="form-select">
            <option value="">Activos</option><option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option><option value="todos" @selected(request('estado') === 'todos')>Todos</option>
        </select></div>
    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Empleado</th><th>No.</th><th>Departamento / Puesto</th><th>Horario</th><th>Equipo</th><th>Ingreso</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse($empleados as $e)
                <tr>
                    <td><a href="{{ route('empleados.show', $e) }}" class="d-flex align-items-center gap-2 text-reset text-decoration-none">
                        @include('partials.avatar', ['e' => $e])
                        <span><span class="fw-semibold">{{ $e->nombre_completo }}</span><br><span class="small text-muted-2">{{ $e->rfc ?: $e->email }}</span></span></a></td>
                    <td class="mono">{{ $e->numero_empleado }}</td>
                    <td>{{ $e->departamento?->nombre ?? '—' }}<br><span class="small text-muted-2">{{ $e->puesto?->nombre }}</span></td>
                    <td>{!! $e->horario ? e($e->horario->nombre) : '<span class="badge-soft soft-warning">Sin horario</span>' !!}</td>
                    <td>{{ $e->device?->nombre ?? '—' }}</td>
                    <td class="text-nowrap">{{ $e->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
                    <td><span class="badge-soft soft-{{ $e->activo ? 'success' : 'secondary' }}">{{ $e->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('empleados.show', $e) }}" class="btn btn-sm btn-light" title="Ver"><i class="bi bi-eye"></i></a>
                        @can('empleados.gestionar')<a href="{{ route('empleados.edit', $e) }}" class="btn btn-sm btn-light" title="Editar"><i class="bi bi-pencil"></i></a>@endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty"><i class="bi bi-people"></i>No se encontraron empleados.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($empleados->hasPages())<div class="card-footer bg-transparent">{{ $empleados->links() }}</div>@endif
</div>
@endsection
