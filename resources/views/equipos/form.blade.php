@extends('layouts.app')
@section('title', $equipo->exists ? 'Editar equipo' : 'Nuevo equipo')
@section('content')
<form method="POST" action="{{ $equipo->exists ? route('equipos.update', $equipo) : route('equipos.store') }}">
    @csrf @if($equipo->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Datos del equipo</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre *</label><input name="nombre" value="{{ old('nombre', $equipo->nombre) }}" class="form-control" placeholder="Ej. Acceso principal" required></div>
                    <div class="col-md-6"><label class="form-label">Sucursal</label>
                        <select name="sucursal_id" class="form-select"><option value="">—</option>
                            @foreach($sucursales as $s)<option value="{{ $s->id }}" @selected(old('sucursal_id', $equipo->sucursal_id) == $s->id)>{{ $s->nombre }}</option>@endforeach</select>
                        @can('catalogos.gestionar')<div class="form-text"><a href="{{ route('catalogos.index', 'sucursales') }}">Administrar sucursales</a></div>@endcan</div>
                    <div class="col-md-12"><label class="form-label">Ubicación (dirección, área)</label><input name="ubicacion" value="{{ old('ubicacion', $equipo->ubicacion) }}" class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">IP en la VPN *</label><input name="ip" value="{{ old('ip', $equipo->ip) }}" class="form-control mono" placeholder="10.0.1.20" required></div>
                    <div class="col-md-3"><label class="form-label">Puerto *</label><input type="number" name="puerto" value="{{ old('puerto', $equipo->puerto) }}" class="form-control" required></div>
                    <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" name="usar_https" id="https" value="1" @checked(old('usar_https', $equipo->usar_https))>
                        <label class="form-check-label" for="https">Usar HTTPS</label></div></div>
                    <div class="col-md-6"><label class="form-label">Usuario ISAPI *</label><input name="usuario" value="{{ old('usuario', $equipo->usuario) }}" class="form-control" autocomplete="off" required></div>
                    <div class="col-md-6"><label class="form-label">Contraseña {{ $equipo->exists ? '' : '*' }}</label><input type="password" name="password" class="form-control" autocomplete="new-password" {{ $equipo->exists ? '' : 'required' }} placeholder="{{ $equipo->exists ? 'Dejar vacío para no cambiar' : '' }}"></div>
                    <div class="col-12"><label class="form-label">Notas</label><textarea name="notas" rows="2" class="form-control">{{ old('notas', $equipo->notas) }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Descarga automática</div>
                <div class="card-body">
                    <label class="form-label">Consultar cada (minutos)</label>
                    <input type="number" name="intervalo_minutos" min="1" max="1440" value="{{ old('intervalo_minutos', $equipo->intervalo_minutos) }}" class="form-control" placeholder="General: {{ \App\Models\Setting::get('intervalo_minutos') }}">
                    <div class="form-text">Déjalo vacío para usar el valor general de Configuración.</div>
                    <div class="form-check form-switch mt-3">
                        <input type="checkbox" class="form-check-input" name="activo" id="activo" value="1" @checked(old('activo', $equipo->activo))>
                        <label class="form-check-label" for="activo">Equipo activo (se consulta automáticamente)</label>
                    </div>
                </div>
            </div>
            <div class="alert alert-info small"><i class="bi bi-shield-check me-1"></i>La plataforma solo <strong>lee</strong> las checadas del equipo. No borra ni modifica información en la terminal.</div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="{{ $equipo->exists ? route('equipos.show', $equipo) : route('equipos.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection
