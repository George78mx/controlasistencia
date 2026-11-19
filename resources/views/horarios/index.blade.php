@extends('layouts.app')
@section('title', 'Horarios')
@section('subtitle', 'Define entradas, salidas, tolerancia y cuándo un retardo cuenta como falta')
@section('actions')<a href="{{ route('horarios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo horario</a>@endsection
@section('content')
<div class="row g-3">
@forelse($horarios as $h)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header">{{ $h->nombre }} <span class="badge-soft soft-{{ $h->activo ? 'success' : 'secondary' }}">{{ $h->activo ? 'Activo' : 'Inactivo' }}</span></div>
            <div class="card-body small">
                @foreach($h->dias as $d)
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span>{{ \App\Models\Horario::DIAS[$d->dia_semana] }}</span>
                        <span class="{{ $d->laborable ? 'fw-semibold' : 'text-muted-2' }}">{{ $d->laborable ? substr($d->entrada,0,5).' – '.substr($d->salida,0,5) : 'Descanso' }}@if($d->laborable && $d->cruzaMedianoche()) <i class="bi bi-moon-stars text-primary" title="Turno nocturno"></i>@endif</span>
                    </div>
                @endforeach
                <div class="d-flex gap-3 mt-3 text-muted-2">
                    <span><i class="bi bi-hourglass me-1"></i>Tolerancia {{ $h->tolerancia_minutos }} min</span>
                    <span><i class="bi bi-x-octagon me-1"></i>Falta desde {{ $h->minutos_para_falta ?: '—' }} min</span>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                <span class="small text-muted-2">{{ $h->empleados_count }} empleados</span>
                <div>
                    <a href="{{ route('horarios.edit', $h) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('horarios.destroy', $h) }}" class="d-inline" data-confirm="¿Eliminar el horario?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="card"><div class="empty"><i class="bi bi-clock"></i>Sin horarios.</div></div></div>
@endforelse
</div>
@endsection
