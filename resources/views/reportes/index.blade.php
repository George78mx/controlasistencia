@extends('layouts.app')
@section('title', 'Reportes')
@section('subtitle', 'Consulta en pantalla o exporta a PDF y Excel')
@section('content')
<div class="row g-3">
@foreach($tipos as $clave => [$titulo, $desc, $icono, $color])
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('reportes.ver', $clave) }}" class="card reporte-card">
            <div class="card-body d-flex gap-3">
                <div class="stat-card"><div class="icon soft-{{ $color }}"><i class="bi bi-{{ $icono }}"></i></div></div>
                <div><h6 class="fw-bold mb-1">{{ $titulo }}</h6><p class="small text-muted-2 mb-0">{{ $desc }}</p></div>
            </div>
        </a>
    </div>
@endforeach
</div>
@endsection
