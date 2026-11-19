@extends('layouts.app')
@section('title', $cfg['titulo'])
@section('content')
@php $c = $cfg['clave']; @endphp
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Agregar {{ $cfg['singular'] }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('catalogos.store', $c) }}" class="row g-3">@csrf
                    @include('catalogos.campos', ['r' => null])
                    <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Agregar</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">{{ $registros->count() }} registros</div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr>
                        @if($c === 'festivos')<th>Fecha</th><th>Descripción</th>
                        @elseif($c === 'tipos-incidencia')<th>Clave</th><th>Nombre</th><th>Con goce</th><th>Estado</th>
                        @else<th>Nombre</th><th>Descripción</th><th>Estado</th>@endif
                        <th></th></tr></thead>
                    <tbody>
                    @forelse($registros as $r)
                        <tr>
                            @if($c === 'festivos')
                                <td class="text-nowrap fw-semibold">{{ $r->fecha->translatedFormat('D d/m/Y') }}</td><td>{{ $r->descripcion }}</td>
                            @elseif($c === 'tipos-incidencia')
                                <td class="mono">{{ $r->clave }}</td>
                                <td><span class="badge-soft" style="background:{{ $r->color }}1f;color:{{ $r->color }}">{{ $r->nombre }}</span></td>
                                <td>{{ $r->con_goce ? 'Sí' : 'No' }}</td>
                                <td><span class="badge-soft soft-{{ $r->activo ? 'success' : 'secondary' }}">{{ $r->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            @else
                                <td class="fw-semibold">{{ $r->nombre }}</td><td class="small text-muted-2">{{ $r->descripcion }}</td>
                                <td><span class="badge-soft soft-{{ $r->activo ? 'success' : 'secondary' }}">{{ $r->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            @endif
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editar{{ $r->id }}"><i class="bi bi-pencil"></i></button>
                                <form method="POST" action="{{ route('catalogos.destroy', [$c, $r->id]) }}" class="d-inline" data-confirm="¿Eliminar este registro?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>
                                <div class="modal fade text-start" id="editar{{ $r->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('catalogos.update', [$c, $r->id]) }}">@csrf @method('PUT')
                                        <div class="modal-header"><h5 class="modal-title">Editar {{ $cfg['singular'] }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body row g-3">@include('catalogos.campos', ['r' => $r])</div>
                                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
                                    </form>
                                </div></div></div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty"><i class="bi bi-{{ $cfg['icono'] }}"></i>Sin registros.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
