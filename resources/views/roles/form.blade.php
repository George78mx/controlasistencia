@extends('layouts.app')
@section('title', $role->exists ? 'Rol: '.$role->name : 'Nuevo rol')
@section('content')
@php $actuales = old('permisos', $role->exists ? $role->permissions->pluck('name')->all() : []); $esAdmin = $role->name === 'Administrador'; @endphp
<form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
    @csrf @if($role->exists) @method('PUT') @endif
    <div class="card mb-3" style="max-width:520px"><div class="card-body">
        <label class="form-label">Nombre del rol *</label>
        <input name="name" value="{{ old('name', $role->name) }}" class="form-control" required {{ $esAdmin ? 'readonly' : '' }}>
        @if($esAdmin)<div class="form-text">El Administrador siempre tiene todos los permisos.</div>@endif
    </div></div>
    <div class="row g-3">
        @foreach($permisos as $grupo => $lista)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100"><div class="card-header">{{ $grupo }}</div><div class="card-body">
                    @foreach($lista as $p)
                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" name="permisos[]" value="{{ $p['name'] }}" id="{{ $p['name'] }}" @checked($esAdmin || in_array($p['name'], $actuales)) @disabled($esAdmin)>
                            <label class="form-check-label" for="{{ $p['name'] }}">{{ $p['desc'] }}<br><span class="mono text-muted-2 small">{{ $p['name'] }}</span></label>
                        </div>
                    @endforeach
                </div></div>
            </div>
        @endforeach
    </div>
    <div class="d-flex gap-2 mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button><a href="{{ route('roles.index') }}" class="btn btn-light">Cancelar</a></div>
</form>
@endsection
