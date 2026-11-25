@extends('layouts.app')
@section('title', 'Configuración')
@section('content')
<form method="POST" action="{{ route('configuracion.update') }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><span><i class="bi bi-hdd-network me-2"></i>Conexión con los equipos</span></div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label">Consultar equipos cada (minutos)</label><input type="number" min="1" max="1440" name="intervalo_minutos" value="{{ old('intervalo_minutos', $valores['intervalo_minutos']) }}" class="form-control" required>
                        <div class="form-text">Valor general. Cada equipo puede tener el suyo.</div></div>
                    <div class="col-md-6"><label class="form-label">Traslape (minutos)</label><input type="number" min="0" name="margen_minutos" value="{{ old('margen_minutos', $valores['margen_minutos']) }}" class="form-control" required>
                        <div class="form-text">Se vuelve a revisar este tiempo hacia atrás para no perder checadas.</div></div>
                    <div class="col-md-6"><label class="form-label">Carga inicial (días)</label><input type="number" min="0" name="dias_carga_inicial" value="{{ old('dias_carga_inicial', $valores['dias_carga_inicial']) }}" class="form-control" required>
                        <div class="form-text">Días que se descargan la primera vez que se conecta un equipo.</div></div>
                    <div class="col-md-6"><label class="form-label">Tiempo de espera (segundos)</label><input type="number" min="5" max="120" name="timeout_segundos" value="{{ old('timeout_segundos', $valores['timeout_segundos']) }}" class="form-control" required></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><span><i class="bi bi-calendar-check me-2"></i>Asistencia</span></div>
                <div class="card-body row g-3">
                    <div class="col-12"><label class="form-label">Nombre de la empresa</label><input name="empresa_nombre" value="{{ old('empresa_nombre', $valores['empresa_nombre']) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Retardos que equivalen a una falta</label><input type="number" min="0" name="retardos_por_falta" value="{{ old('retardos_por_falta', $valores['retardos_por_falta']) }}" class="form-control" required>
                        <div class="form-text">0 = no se convierten.</div></div>
                    <div class="col-12"><label class="form-label">Aceptar salida desde (minutos antes de la hora de salida)</label><input type="number" min="0" max="720" name="ventana_salida_minutos" value="{{ old('ventana_salida_minutos', $valores['ventana_salida_minutos']) }}" class="form-control" required>
                        <div class="form-text">Antes de ese momento las checadas intermedias no se muestran como salida. Al cerrar el día se toma siempre la última checada.</div></div>
                    <div class="col-md-6"><label class="form-label">Checadas repetidas (minutos)</label><input type="number" min="0" name="checada_minima_minutos" value="{{ old('checada_minima_minutos', $valores['checada_minima_minutos']) }}" class="form-control" required>
                        <div class="form-text">Si la última checada está a menos de estos minutos de la primera, no se toma como salida.</div></div>
                    <div class="col-12 small text-muted-2"><i class="bi bi-info-circle me-1"></i>La tolerancia y los minutos para falta se definen en cada <a href="{{ route('horarios.index') }}">horario</a>.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="alert alert-info small mt-3"><i class="bi bi-terminal me-1"></i>Para que la descarga automática funcione debe estar corriendo el programador de tareas de Laravel (<code>php artisan schedule:work</code> o una tarea programada de Windows cada minuto con <code>php artisan schedule:run</code>). Ver INSTALACION.md.</div>
    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar configuración</button>
</form>
@endsection
