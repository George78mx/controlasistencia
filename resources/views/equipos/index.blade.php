@extends('layouts.app')
@section('title', 'Equipos')
@section('subtitle', 'Terminales Hikvision conectadas por VPN')
@section('actions')
    @can('equipos.gestionar')<a href="{{ route('equipos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo equipo</a>@endcan
@endsection
@section('content')
<div class="row g-3">
@forelse($equipos as $eq)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="icon soft-primary rounded-3 d-grid" style="width:48px;height:48px;place-items:center;font-size:1.4rem"><i class="bi bi-person-bounding-box"></i></div>
                    <div class="flex-grow-1">
                        <a href="{{ route('equipos.show', $eq) }}" class="fw-bold fs-6 text-reset text-decoration-none">{{ $eq->nombre }}</a>
                        <div class="small text-muted-2">{{ $eq->ubicacion ?: 'Sin ubicación' }}</div>
                    </div>
                    @if(!$eq->activo)
                        <span class="badge-soft soft-secondary">Inactivo</span>
                    @elseif($eq->en_linea)
                        <span class="badge-soft soft-success"><span class="status-dot on me-1" style="width:7px;height:7px"></span>En línea</span>
                    @else
                        <span class="badge-soft soft-danger">Sin conexión</span>
                    @endif
                </div>
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted-2 fw-normal">Dirección</dt><dd class="col-7 mono">{{ $eq->baseUrl() }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Modelo</dt><dd class="col-7">{{ $eq->modelo ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Consulta cada</dt><dd class="col-7">{{ $eq->intervalo() }} min {!! $eq->intervalo_minutos ? '' : '<span class="text-muted-2">(general)</span>' !!}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Última consulta</dt><dd class="col-7">{{ $eq->ultima_sincronizacion_at?->diffForHumans() ?? 'Nunca' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Checadas</dt><dd class="col-7">{{ number_format($eq->checadas_count) }} <span class="text-muted-2">({{ $eq->checadas_hoy_count }} hoy)</span></dd>
                </dl>
                @if($eq->ultimo_error)
                    <div class="alert alert-danger small py-2 mt-3 mb-0 text-break"><i class="bi bi-exclamation-triangle me-1"></i>{{ Str::limit($eq->ultimo_error, 140) }}</div>
                @endif
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <a href="{{ route('equipos.show', $eq) }}" class="btn btn-sm btn-light flex-fill"><i class="bi bi-eye me-1"></i>Detalle</a>
                @can('equipos.sincronizar')
                <form method="POST" action="{{ route('equipos.sincronizar', $eq) }}" class="flex-fill" data-loading="Descargando">@csrf
                    <button class="btn btn-sm btn-outline-primary w-100" type="submit"><i class="bi bi-cloud-download me-1"></i>Descargar</button>
                </form>
                @endcan
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="card"><div class="empty"><i class="bi bi-hdd-network"></i>No hay equipos registrados.<br>
        @can('equipos.gestionar')<a href="{{ route('equipos.create') }}" class="btn btn-primary mt-3">Registrar el primer equipo</a>@endcan</div></div></div>
@endforelse
</div>
@endsection
