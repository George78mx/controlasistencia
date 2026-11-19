<thead><tr>
    <th>No.</th><th>Empleado</th><th>Departamento</th><th>Días lab.</th><th>Asistencias</th><th>Retardos</th><th>Min. retardo</th>
    <th>Faltas</th><th>Faltas x retardos</th><th>Total faltas</th><th>Justificadas</th><th>Desc./Fest.</th><th>Horas</th><th>% Asist.</th>
</tr></thead>
<tbody>
@forelse($filas as $r)
<tr>
    <td>{{ $r->empleado?->numero_empleado }}</td>
    <td>{{ $r->empleado?->nombre_completo }}</td>
    <td>{{ $r->empleado?->departamento?->nombre }}</td>
    <td class="num">{{ $r->laborables }}</td>
    <td class="num">{{ $r->asistencias }}</td>
    <td class="num">{{ $r->retardos }}</td>
    <td class="num">{{ $r->minutos_retardo }}</td>
    <td class="num">{{ $r->faltas }}</td>
    <td class="num">{{ $r->faltas_por_retardo }}</td>
    <td class="num"><strong>{{ $r->faltas_totales }}</strong></td>
    <td class="num">{{ $r->justificadas }}</td>
    <td class="num">{{ $r->descansos }}</td>
    <td class="num">{{ \App\Models\Asistencia::minutosAHoras($r->horas) }}</td>
    <td class="num">{{ $r->porcentaje }}%</td>
</tr>
@empty
<tr><td colspan="14">Sin información para el periodo seleccionado.</td></tr>
@endforelse
@if($filas->count())
<tr class="total">
    <td></td><td><strong>Totales ({{ $filas->count() }} empleados)</strong></td><td></td>
    @foreach(['laborables', 'asistencias', 'retardos', 'minutos_retardo', 'faltas', 'faltas_por_retardo', 'faltas_totales', 'justificadas', 'descansos'] as $c)
        <td class="num"><strong>{{ $filas->sum($c) }}</strong></td>
    @endforeach
    <td class="num"><strong>{{ \App\Models\Asistencia::minutosAHoras($filas->sum('horas')) }}</strong></td><td></td>
</tr>
@endif
</tbody>
