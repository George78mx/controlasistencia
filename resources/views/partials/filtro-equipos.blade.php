{{-- Selección rápida del equipo checador. Conserva los demás filtros de la página. --}}
@php
    $equiposFiltro = \App\Models\Device::query()
        ->when(\App\Support\SucursalActiva::id(), fn ($q, $id) => $q->where('sucursal_id', $id))
        ->orderBy('nombre')->get(['id', 'nombre', 'en_linea', 'activo']);
    $actual = request('device_id');
    $base = request()->except(['device_id', 'page']);
@endphp
@if($equiposFiltro->count() > 1 || $actual)
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="small text-muted-2 me-1"><i class="bi bi-hdd-network me-1"></i>Equipo:</span>
    <a href="{{ request()->url().($base ? '?'.http_build_query($base) : '') }}" class="btn btn-sm {{ $actual ? 'btn-light' : 'btn-primary' }} rounded-pill">Todos</a>
    @foreach($equiposFiltro as $eq)
        <a href="{{ request()->url().'?'.http_build_query($base + ['device_id' => $eq->id]) }}"
           class="btn btn-sm {{ $actual == $eq->id ? 'btn-primary' : 'btn-light' }} rounded-pill">
            <span class="status-dot {{ !$eq->activo ? 'idle' : ($eq->en_linea ? 'on' : 'off') }} me-1" style="width:7px;height:7px"></span>{{ $eq->nombre }}
        </a>
    @endforeach
</div>
@endif
