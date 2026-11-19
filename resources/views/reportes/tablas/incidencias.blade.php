<thead><tr><th>No.</th><th>Empleado</th><th>Departamento</th><th>Tipo</th><th>Con goce</th><th>Desde</th><th>Hasta</th><th>Días</th><th>Folio</th><th>Motivo</th><th>Registró</th></tr></thead>
<tbody>
@forelse($registros as $i)
<tr>
    <td>{{ $i->empleado?->numero_empleado }}</td>
    <td>{{ $i->empleado?->nombre_completo }}</td>
    <td>{{ $i->empleado?->departamento?->nombre }}</td>
    <td>{{ $i->tipo?->nombre }}</td>
    <td>{{ $i->tipo?->con_goce ? 'Sí' : 'No' }}</td>
    <td>{{ $i->fecha_inicio->format('d/m/Y') }}</td>
    <td>{{ $i->fecha_fin->format('d/m/Y') }}</td>
    <td class="num">{{ $i->dias }}</td>
    <td>{{ $i->folio }}</td>
    <td>{{ $i->motivo }}</td>
    <td>{{ $i->usuario?->name }}</td>
</tr>
@empty
<tr><td colspan="11">Sin incidencias en el periodo seleccionado.</td></tr>
@endforelse
@if($registros->count())
<tr class="total"><td colspan="7"><strong>Total: {{ $registros->count() }} incidencias</strong></td><td class="num"><strong>{{ $registros->sum('dias') }}</strong></td><td colspan="3"></td></tr>
@endif
</tbody>
