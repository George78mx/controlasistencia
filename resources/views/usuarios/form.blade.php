@extends('layouts.app')
@section('title', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('content')
<div class="card" style="max-width:760px">
    <div class="card-body">
        <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="row g-3">
            @csrf @if($usuario->exists) @method('PUT') @endif
            <div class="col-md-6"><label class="form-label">Nombre *</label><input name="name" value="{{ old('name', $usuario->name) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Correo *</label><input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Contraseña {{ $usuario->exists ? '' : '*' }}</label><input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="{{ $usuario->exists ? 'Dejar vacío para no cambiar' : '' }}"></div>
            <div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
            <div class="col-12"><label class="form-label">Roles</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($roles as $r)
                        <div class="form-check"><input type="checkbox" class="form-check-input" name="roles[]" value="{{ $r->name }}" id="r{{ $r->id }}" @checked(in_array($r->name, old('roles', $usuario->exists ? $usuario->getRoleNames()->all() : [])))><label class="form-check-label" for="r{{ $r->id }}">{{ $r->name }}</label></div>
                    @endforeach
                </div></div>
            <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="activo" value="1" id="activo" class="form-check-input" @checked(old('activo', $usuario->activo))><label for="activo" class="form-check-label">Usuario activo (puede iniciar sesión)</label></div></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button><a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a></div>
        </form>
    </div>
</div>
@endsection
