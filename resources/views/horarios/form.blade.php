@extends('layouts.app')
@section('title', $horario->exists ? 'Editar horario' : 'Nuevo horario')
@section('content')
<form method="POST" action="{{ $horario->exists ? route('horarios.update', $horario) : route('horarios.store') }}">
    @csrf @if($horario->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">General</div>
                <div class="card-body row g-3">
                    <div class="col-12"><label class="form-label">Nombre *</label><input name="nombre" value="{{ old('nombre', $horario->nombre) }}" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Descripción</label><input name="descripcion" value="{{ old('descripcion', $horario->descripcion) }}" class="form-control"></div>
                    <div class="col-6"><label class="form-label">Tolerancia (min)</label><input type="number" min="0" name="tolerancia_minutos" value="{{ old('tolerancia_minutos', $horario->tolerancia_minutos) }}" class="form-control" required>
                        <div class="form-text">Minutos después de la entrada que no cuentan como retardo.</div></div>
                    <div class="col-6"><label class="form-label">Falta a partir de (min)</label><input type="number" min="0" name="minutos_para_falta" value="{{ old('minutos_para_falta', $horario->minutos_para_falta) }}" class="form-control" required>
                        <div class="form-text">Si el retardo llega a estos minutos se registra falta. 0 = nunca.</div></div>
                    <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="activo" value="1" id="activo" class="form-check-input" @checked(old('activo', $horario->activo))><label class="form-check-label" for="activo">Activo</label></div></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Días y horas</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Día</th><th>Laborable</th><th>Entrada</th><th>Salida</th></tr></thead>
                        <tbody>
                        @foreach(\App\Models\Horario::DIAS as $n => $nombre)
                            @php $d = $horario->exists ? $horario->dias->firstWhere('dia_semana', $n) : null; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $nombre }}</td>
                                <td><div class="form-check form-switch"><input type="checkbox" class="form-check-input" name="dias[{{ $n }}][laborable]" value="1" @checked(old("dias.$n.laborable", $d?->laborable ?? $n <= 5))></div></td>
                                <td><input type="time" name="dias[{{ $n }}][entrada]" value="{{ old("dias.$n.entrada", $d?->entrada ? substr($d->entrada, 0, 5) : '09:00') }}" class="form-control form-control-sm"></td>
                                <td><input type="time" name="dias[{{ $n }}][salida]" value="{{ old("dias.$n.salida", $d?->salida ? substr($d->salida, 0, 5) : '18:00') }}" class="form-control form-control-sm"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body pt-0 small text-muted-2"><i class="bi bi-moon-stars me-1"></i>Si la salida es menor que la entrada (ej. 22:00 a 06:00) se toma como turno nocturno que termina al día siguiente.</div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="{{ route('horarios.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection
