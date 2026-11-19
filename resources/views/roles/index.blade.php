@extends('layouts.app')
@section('title', 'Roles y permisos')
@section('actions')<a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo rol</a>@endsection
@section('content')
<div class="row g-3">
@foreach($roles as $r)
    <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="icon soft-primary rounded-3 d-grid mb-3" style="width:44px;height:44px;place-items:center;font-size:1.2rem"><i class="bi bi-shield-lock"></i></div>
            <h6 class="fw-bold">{{ $r->name }}</h6>
            <div class="small text-muted-2">{{ $r->users_count }} usuarios · {{ $r->name === 'Administrador' ? 'todos los' : $r->permissions_count }} permisos</div>
        </div>
        <div class="card-footer bg-transparent d-flex gap-2">
            <a href="{{ route('roles.edit', $r) }}" class="btn btn-sm btn-light flex-fill"><i class="bi bi-pencil me-1"></i>Permisos</a>
            @if($r->name !== 'Administrador')
            <form method="POST" action="{{ route('roles.destroy', $r) }}" data-confirm="¿Eliminar el rol {{ $r->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>
            @endif
        </div></div>
    </div>
@endforeach
</div>
@endsection
