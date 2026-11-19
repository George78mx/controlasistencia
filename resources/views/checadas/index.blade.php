@extends('layouts.app')
@section('title', 'Checadas')
@section('subtitle', 'Registros descargados de los equipos')
@section('content')
<form class="filters row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="desde" value="{{ $desde }}" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="hasta" value="{{ $hasta }}" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">Equipo</label>
        <select name="device_id" class="form-select"><option value="">Todos</option>
            @foreach($equipos as $d)<option value="{{ $d->id }}" @selected(request('device_id') == $d->id)>{{ $d->nombre }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Empleado</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Nombre o número"></div>
    <div class="col-md-2"><div class="form-check mb-2"><input type="checkbox" name="sin_empleado" value="1" id="se" class="form-check-input" @checked(request('sin_empleado'))><label for="se" class="form-check-label small">Solo no registrados</label></div></div>
    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
</form>
<div class="card">
    <div class="card-header">{{ number_format($checadas->total()) }} checadas</div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Fecha</th><th>Hora</th><th>Empleado</th><th>No.</th><th>Equipo</th><th>Tipo</th><th>Método</th><th>Origen</th><th>Serie</th></tr></thead>
            <tbody>
            @forelse($checadas as $c)
                <tr>
                    <td class="text-nowrap">{{ $c->fecha_hora->translatedFormat('D d/m/Y') }}</td>
                    <td class="fw-semibold">{{ $c->fecha_hora->format('H:i:s') }}</td>
                    <td>@if($c->empleado)<a href="{{ route('empleados.show', $c->empleado) }}" class="text-reset">{{ $c->empleado->nombre_completo }}</a>@else<span class="badge-soft soft-warning">No registrado</span>@endif</td>
                    <td class="mono">{{ $c->numero_empleado }}</td>
                    <td>{{ $c->device?->nombre }}</td>
                    <td>{{ $c->tipo_texto }}</td>
                    <td class="small">{{ $c->metodo_texto }}</td>
                    <td><span class="badge-soft soft-{{ $c->origen === 'push' ? 'info' : 'secondary' }}">{{ $c->origen === 'push' ? 'Tiempo real' : 'Consulta' }}</span></td>
                    <td class="mono small text-muted-2">{{ $c->serial_no }}</td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="empty"><i class="bi bi-fingerprint"></i>Sin checadas en el periodo.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($checadas->hasPages())<div class="card-footer bg-transparent">{{ $checadas->links() }}</div>@endif
</div>
@endsection
