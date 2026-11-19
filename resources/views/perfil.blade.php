@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ route('perfil.update') }}">
            @csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Nombre</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Correo</label><input value="{{ $user->email }}" class="form-control" disabled></div>
            <hr>
            <h6 class="fw-semibold">Cambiar contraseña</h6>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Contraseña actual</label><input type="password" name="password_actual" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Nueva contraseña</label><input type="password" name="password" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Confirmar</label><input type="password" name="password_confirmation" class="form-control"></div>
            </div>
            <div class="form-text">Mínimo 8 caracteres con letras y números.</div>
            <button class="btn btn-primary mt-3">Guardar</button>
        </form>
    </div>
</div>
@endsection
