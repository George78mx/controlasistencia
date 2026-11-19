@extends('layouts.app')
@section('title', $empleado->exists ? 'Editar empleado' : 'Nuevo empleado')
@section('content')
@php $v = fn($c) => old($c, $empleado->$c instanceof \Carbon\Carbon ? $empleado->$c->toDateString() : $empleado->$c); @endphp
<form method="POST" enctype="multipart/form-data" action="{{ $empleado->exists ? route('empleados.update', $empleado) : route('empleados.store') }}">
    @csrf @if($empleado->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header">Datos personales</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label">No. de empleado *</label><input name="numero_empleado" value="{{ $v('numero_empleado') }}" class="form-control mono" required>
                        <div class="form-text">Igual al <em>Employee No.</em> de la terminal.</div></div>
                    <div class="col-md-8"><label class="form-label">Nombre(s) *</label><input name="nombre" value="{{ $v('nombre') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Apellido paterno</label><input name="apellido_paterno" value="{{ $v('apellido_paterno') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Apellido materno</label><input name="apellido_materno" value="{{ $v('apellido_materno') }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">RFC</label><input name="rfc" value="{{ $v('rfc') }}" maxlength="13" class="form-control text-uppercase mono"></div>
                    <div class="col-md-5"><label class="form-label">CURP</label><input name="curp" value="{{ $v('curp') }}" maxlength="18" class="form-control text-uppercase mono"></div>
                    <div class="col-md-3"><label class="form-label">NSS</label><input name="nss" value="{{ $v('nss') }}" maxlength="11" class="form-control mono"></div>
                    <div class="col-md-4"><label class="form-label">Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" value="{{ $v('fecha_nacimiento') }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Género</label>
                        <select name="genero" class="form-select"><option value=""></option>
                            @foreach(['M' => 'Masculino', 'F' => 'Femenino', 'X' => 'Otro'] as $k => $t)<option value="{{ $k }}" @selected($v('genero') === $k)>{{ $t }}</option>@endforeach
                        </select></div>
                    <div class="col-md-5"><label class="form-label">Correo</label><input type="email" name="email" value="{{ $v('email') }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Teléfono</label><input name="telefono" value="{{ $v('telefono') }}" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label">Dirección</label><input name="direccion" value="{{ $v('direccion') }}" class="form-control"></div>
                    <div class="col-md-7"><label class="form-label">Contacto de emergencia</label><input name="contacto_emergencia" value="{{ $v('contacto_emergencia') }}" class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">Teléfono de emergencia</label><input name="telefono_emergencia" value="{{ $v('telefono_emergencia') }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Notas</label><textarea name="notas" rows="2" class="form-control">{{ $v('notas') }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-3">
                <div class="card-header">Datos laborales</div>
                <div class="card-body row g-3">
                    <div class="col-12"><label class="form-label">Departamento</label>
                        <select name="departamento_id" class="form-select"><option value="">—</option>
                            @foreach($departamentos as $d)<option value="{{ $d->id }}" @selected($v('departamento_id') == $d->id)>{{ $d->nombre }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Puesto</label>
                        <select name="puesto_id" class="form-select"><option value="">—</option>
                            @foreach($puestos as $d)<option value="{{ $d->id }}" @selected($v('puesto_id') == $d->id)>{{ $d->nombre }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Horario</label>
                        <select name="horario_id" class="form-select"><option value="">Sin horario</option>
                            @foreach($horarios as $d)<option value="{{ $d->id }}" @selected($v('horario_id') == $d->id)>{{ $d->nombre }}</option>@endforeach</select>
                        <div class="form-text">Necesario para calcular retardos y faltas.</div></div>
                    <div class="col-12"><label class="form-label">Equipo principal</label>
                        <select name="device_id" class="form-select"><option value="">—</option>
                            @foreach($equipos as $d)<option value="{{ $d->id }}" @selected($v('device_id') == $d->id)>{{ $d->nombre }}</option>@endforeach</select>
                        <div class="form-text">Las checadas de cualquier equipo cuentan para su asistencia.</div></div>
                    <div class="col-6"><label class="form-label">Fecha de ingreso</label><input type="date" name="fecha_ingreso" value="{{ $v('fecha_ingreso') }}" class="form-control"></div>
                    <div class="col-6"><label class="form-label">Fecha de baja</label><input type="date" name="fecha_baja" value="{{ $v('fecha_baja') }}" class="form-control"></div>
                    <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="activo" value="1" id="activo" class="form-check-input" @checked($v('activo'))><label for="activo" class="form-check-label">Empleado activo</label></div></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header">Fotografía</div>
                <div class="card-body d-flex align-items-center gap-3">
                    @include('partials.avatar', ['e' => $empleado, 'clase' => 'avatar-lg'])
                    <input type="file" name="foto" accept="image/*" class="form-control">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="{{ $empleado->exists ? route('empleados.show', $empleado) : route('empleados.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection
