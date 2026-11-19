<thead><tr><th>Fecha</th><th>No.</th><th>Empleado</th><th>Departamento</th><th>Entrada prog.</th><th>Entrada real</th><th>Minutos</th><th>Estatus</th></tr></thead>
<tbody>
@forelse($registros as $a)
<tr>
    <td>{{ $a->fecha->format('d/m/Y') }}</td>
    <td>{{ $a->empleado?->numero_empleado }}</td>
    <td>{{ $a->empleado?->nombre_completo }}</td>
    <td>{{ $a->empleado?->departamento?->nombre }}</td>
    <td>{{ $a->entrada_programada?->format('H:i') }}</td>
    <td>{{ $a->entrada?->format('H:i') }}</td>
    <td class="num">{{ $a->minutos_retardo }}</td>
    <td class="estatus-{{ $a->estatus }}">{{ $a->estatus_texto }}</td>
</tr>
@empty
<tr><td colspan="8">Sin retardos en el periodo seleccionado.</td></tr>
@endforelse
@if($registros->count())
<tr class="total"><td colspan="6"><strong>Total: {{ $registros->count() }} retardos</strong></td><td class="num"><strong>{{ $registros->sum('minutos_retardo') }}</strong></td><td></td></tr>
@endif
</tbody>
