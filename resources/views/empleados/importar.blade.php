@extends('layouts.app')
@section('title', 'Importar empleados')
@section('subtitle', 'Alta y actualización masiva desde Excel o CSV')
@section('actions')
    <a href="{{ route('empleados.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Empleados</a>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><span><span class="badge-soft soft-primary me-2">1</span>Descarga la plantilla</span></div>
            <div class="card-body">
                <p class="small text-muted-2">Archivo Excel con las columnas correctas, listas desplegables de departamentos, puestos, horarios y equipos, y una hoja de instrucciones.</p>
                <a href="{{ route('empleados.plantilla') }}" class="btn btn-success w-100 mb-2"><i class="bi bi-file-earmark-excel me-1"></i>Plantilla vacía</a>
                <a href="{{ route('empleados.plantilla', ['con_empleados' => 1]) }}" class="btn btn-outline-success w-100"><i class="bi bi-people me-1"></i>Plantilla con los empleados actuales</a>
                <div class="form-text">La segunda sirve para editar datos de muchos empleados a la vez.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><span><span class="badge-soft soft-primary me-2">2</span>Sube el archivo</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('empleados.importar.procesar') }}" enctype="multipart/form-data" class="row g-3" id="form-importar">
                    @csrf
                    <input type="hidden" name="accion" value="simular" id="accion">
                    <div class="col-12">
                        <input type="file" name="archivo" class="form-control form-control-lg" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Excel (.xlsx, .xls) o CSV, máximo 10 MB. Se lee la primera hoja.</div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="actualizar" value="1" id="actualizar" @checked(old('actualizar', true))>
                            <label class="form-check-label" for="actualizar">Actualizar empleados que ya existen</label>
                        </div>
                        <div class="form-text">Se identifican por No. de empleado. Las celdas vacías no borran datos.</div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="crear_catalogos" value="1" id="crear_catalogos" @checked(old('crear_catalogos'))>
                            <label class="form-check-label" for="crear_catalogos">Crear departamentos y puestos que no existan</label>
                        </div>
                        <div class="form-text">Horarios y equipos deben existir antes de importar.</div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" data-accion="simular" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Validar sin guardar</button>
                        <button type="submit" data-accion="importar" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Importar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($resultado)
    @php
        $r = $resultado['resumen'];
        $simulado = $resultado['opciones']['simular'];
        $acciones = [
            'creado' => [$simulado ? 'Por crear' : 'Creados', 'success'],
            'actualizado' => [$simulado ? 'Por actualizar' : 'Actualizados', 'info'],
            'sin_cambios' => ['Sin cambios', 'secondary'],
            'omitido' => ['Omitidos', 'light'],
            'error' => ['Con error', 'danger'],
        ];
    @endphp
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-{{ $simulado ? 'search' : 'check2-circle' }} me-2"></i>{{ $simulado ? 'Resultado de la validación' : 'Resultado de la importación' }} · {{ $resultado['archivo'] }}</span>
            <div class="d-flex flex-wrap gap-2">
                @foreach($acciones as $k => [$t, $c])
                    <span class="badge-estatus soft-{{ $c }}">{{ $t }}: {{ $r[$k] }}</span>
                @endforeach
            </div>
        </div>
        @if($simulado && ($r['creado'] + $r['actualizado']) > 0)
            <div class="card-body border-bottom py-2 small">
                <i class="bi bi-info-circle me-1"></i>No se guardó nada. Si el resultado es correcto, vuelve a seleccionar el archivo y presiona <strong>Importar</strong>.
                @if($r['error']) Las filas con error se omitirán. @endif
            </div>
        @endif
        <div class="table-responsive" style="max-height:560px">
            <table class="table table-hover table-sm">
                <thead><tr><th>Fila</th><th>No. empleado</th><th>Nombre</th><th>Resultado</th><th>Detalle</th></tr></thead>
                <tbody>
                @forelse($resultado['filas'] as $f)
                    @php
                        $c = $acciones[$f['accion']][1] ?? 'secondary';
                        $t = ['creado' => $simulado ? 'Se creará' : 'Creado', 'actualizado' => $simulado ? 'Se actualizará' : 'Actualizado',
                              'sin_cambios' => 'Sin cambios', 'omitido' => 'Omitido', 'error' => 'Error'][$f['accion']] ?? $f['accion'];
                    @endphp
                    <tr>
                        <td>{{ $f['fila'] }}</td>
                        <td class="mono">{{ $f['numero'] }}</td>
                        <td>{{ $f['nombre'] }}</td>
                        <td><span class="badge-soft soft-{{ $c }}">{{ $t }}</span></td>
                        <td class="small">@foreach($f['mensajes'] as $m)<div class="{{ $f['accion'] === 'error' ? 'text-danger' : 'text-muted-2' }}">{{ $m }}</div>@endforeach</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty"><i class="bi bi-file-earmark"></i>El archivo no tenía filas con datos.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header">Columnas de la plantilla</div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Columna</th><th>Obligatoria</th><th>Descripción</th></tr></thead>
                <tbody>
                @foreach($columnas as [$titulo, $obl, $ancho, $desc])
                    <tr><td class="mono">{{ $titulo }}</td><td>{!! $obl ? '<span class="badge-soft soft-danger">Sí</span>' : '' !!}</td><td class="small text-muted-2">{{ $desc }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
@push('scripts')
<script>
document.getElementById('form-importar').addEventListener('submit', function (e) {
    const boton = e.submitter;
    const accion = boton?.dataset.accion === 'importar' ? 'importar' : 'simular';
    if (accion === 'importar' && !confirm('¿Importar el archivo? Se crearán y actualizarán empleados.')) { e.preventDefault(); return; }
    document.getElementById('accion').value = accion;
    this.querySelectorAll('[type=submit]').forEach(b => b.disabled = true);
    if (boton) boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (accion === 'importar' ? 'Importando' : 'Validando');
});
</script>
@endpush
