<thead><tr><th>Fecha</th><th>Día</th><th>No.</th><th>Empleado</th><th>Departamento</th><th>Puesto</th><th>Tipo de falta</th><th>Entrada registrada</th></tr></thead>
<tbody>
@forelse($registros as $a)
<tr>
    <td>{{ $a->fecha->format('d/m/Y') }}</td>
    <td>{{ ucfirst($a->fecha->translatedFormat('l')) }}</td>
    <td>{{ $a->empleado?->numero_empleado }}</td>
    <td>{{ $a->empleado?->nombre_completo }}</td>
    <td>{{ $a->empleado?->departamento?->nombre }}</td>
    <td>{{ $a->empleado?->puesto?->nombre }}</td>
    <td>{{ $a->falta_por_retardo ? 'Por retardo ('.$a->minutos_retardo.' min)' : 'Sin registro' }}</td>
    <td>{{ $a->entrada?->format('H:i') }}</td>
</tr>
@empty
<tr><td colspan="8">Sin faltas en el periodo seleccionado.</td></tr>
@endforelse
@if($registros->count())
<tr class="total"><td colspan="8"><strong>Total: {{ $registros->count() }} faltas</strong></td></tr>
@endif
</tbody>
