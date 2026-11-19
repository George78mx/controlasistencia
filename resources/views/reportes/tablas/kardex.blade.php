<thead><tr><th>Fecha</th><th>Día</th><th>Entrada prog.</th><th>Salida prog.</th><th>Entrada</th><th>Salida</th><th>Retardo (min)</th><th>Salida antic. (min)</th><th>Horas</th><th>Checadas</th><th>Estatus</th><th>Observaciones</th></tr></thead>
<tbody>
@forelse($registros as $a)
<tr>
    <td>{{ $a->fecha->format('d/m/Y') }}</td>
    <td>{{ ucfirst($a->fecha->translatedFormat('l')) }}</td>
    <td>{{ $a->entrada_programada?->format('H:i') }}</td>
    <td>{{ $a->salida_programada?->format('H:i') }}</td>
    <td>{{ $a->entrada?->format('H:i') }}</td>
    <td>{{ $a->salida?->format('H:i') }}</td>
    <td class="num">{{ $a->minutos_retardo ?: '' }}</td>
    <td class="num">{{ $a->minutos_salida_anticipada ?: '' }}</td>
    <td class="num">{{ $a->minutos_trabajados ? \App\Models\Asistencia::minutosAHoras($a->minutos_trabajados) : '' }}</td>
    <td class="num">{{ $a->num_checadas }}</td>
    <td class="estatus-{{ $a->estatus }}">{{ $a->estatus_texto }}</td>
    <td>{{ $a->estatus === 'justificada' ? $a->incidencia?->motivo : $a->observaciones }}</td>
</tr>
@empty
<tr><td colspan="12">{{ $empleado ? 'Sin información para el periodo seleccionado.' : 'Selecciona un empleado.' }}</td></tr>
@endforelse
</tbody>
