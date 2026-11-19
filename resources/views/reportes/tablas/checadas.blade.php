<thead><tr><th>Fecha</th><th>Hora</th><th>No.</th><th>Empleado</th><th>Departamento</th><th>Equipo</th><th>Tipo</th><th>Método</th></tr></thead>
<tbody>
@forelse($registros as $c)
<tr>
    <td>{{ $c->fecha_hora->format('d/m/Y') }}</td>
    <td>{{ $c->fecha_hora->format('H:i:s') }}</td>
    <td>{{ $c->numero_empleado }}</td>
    <td>{{ $c->empleado?->nombre_completo ?? 'No registrado' }}</td>
    <td>{{ $c->empleado?->departamento?->nombre }}</td>
    <td>{{ $c->device?->nombre }}</td>
    <td>{{ $c->tipo_texto }}</td>
    <td>{{ $c->metodo_texto }}</td>
</tr>
@empty
<tr><td colspan="8">Sin checadas en el periodo seleccionado.</td></tr>
@endforelse
@if($total > $registros->count())
<tr><td colspan="8">Se muestran {{ $registros->count() }} de {{ $total }} checadas. Reduce el periodo para ver todas.</td></tr>
@endif
</tbody>
