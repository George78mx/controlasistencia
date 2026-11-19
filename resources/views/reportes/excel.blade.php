<table>
    <tr><td><strong>{{ $empresa }}</strong></td></tr>
    <tr><td><strong>{{ $titulo }}</strong></td></tr>
    <tr><td>Periodo: {{ $periodo }}{{ $filtroTexto ? ' · '.$filtroTexto : '' }}</td></tr>
    <tr><td>Generado: {{ now()->format('d/m/Y H:i') }}</td></tr>
    @if($tipo === 'kardex' && $empleado)
        <tr><td>Empleado: {{ $empleado->nombre_completo }} (No. {{ $empleado->numero_empleado }}) · {{ $empleado->departamento?->nombre }} · Horario: {{ $empleado->horario?->nombre ?? 'Sin horario' }}</td></tr>
        @foreach($resumen as $k => $val)<tr><td>{{ $k }}</td><td>{{ $val }}</td></tr>@endforeach
    @endif
    <tr><td></td></tr>
    @include('reportes.tablas.'.$tipo)
</table>
