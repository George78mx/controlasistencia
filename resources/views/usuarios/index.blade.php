@extends('layouts.app')
@section('title', 'Usuarios')
@section('actions')<a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Nuevo usuario</a>@endsection
@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Usuario</th><th>Correo</th><th>Roles</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @foreach($usuarios as $u)
                <tr>
                    <td><span class="avatar me-2">{{ mb_strtoupper(mb_substr($u->name, 0, 2)) }}</span><span class="fw-semibold">{{ $u->name }}</span></td>
                    <td>{{ $u->email }}</td>
                    <td>@foreach($u->roles as $r)<span class="badge-soft soft-primary me-1">{{ $r->name }}</span>@endforeach</td>
                    <td class="small text-muted-2">{{ $u->ultimo_acceso_at?->diffForHumans() ?? 'Nunca' }}</td>
                    <td><span class="badge-soft soft-{{ $u->activo ? 'success' : 'secondary' }}">{{ $u->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('usuarios.destroy', $u) }}" class="d-inline" data-confirm="¿Eliminar el usuario {{ $u->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
