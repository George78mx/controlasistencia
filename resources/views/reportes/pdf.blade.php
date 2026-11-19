<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 90px 28px 40px 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #0f172a; }
    header { position: fixed; top: -72px; left: 0; right: 0; height: 60px; border-bottom: 2px solid #4f46e5; }
    header .empresa { font-size: 13px; font-weight: bold; color: #4f46e5; }
    header .titulo { font-size: 11px; font-weight: bold; margin-top: 2px; }
    header .meta { font-size: 8px; color: #64748b; margin-top: 2px; }
    footer { position: fixed; bottom: -28px; left: 0; right: 0; font-size: 7.5px; color: #94a3b8; }
    footer .pagenum:before { content: counter(page); }
    table.datos { width: 100%; border-collapse: collapse; }
    table.datos th { background: #4f46e5; color: #fff; padding: 5px 4px; text-align: left; font-size: 7.5px; text-transform: uppercase; }
    table.datos td { padding: 4px; border-bottom: 1px solid #e5e7eb; }
    table.datos tr:nth-child(even) td { background: #f8fafc; }
    table.datos td.num { text-align: right; }
    table.datos tr.total td { background: #eef2ff; border-top: 1.5px solid #4f46e5; }
    .estatus-falta { color: #dc2626; font-weight: bold; } .estatus-retardo { color: #d97706; font-weight: bold; }
    .estatus-justificada { color: #0891b2; } .estatus-asistencia { color: #059669; }
    .resumen { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
    .resumen td { border: 1px solid #e5e7eb; padding: 6px; text-align: center; }
    .resumen .v { font-size: 12px; font-weight: bold; } .resumen .k { font-size: 7px; color: #64748b; text-transform: uppercase; }
    .ficha { margin-bottom: 8px; font-size: 9.5px; }
</style>
</head>
<body>
<header>
    <div class="empresa">{{ $empresa }}</div>
    <div class="titulo">{{ $titulo }}</div>
    <div class="meta">Periodo: {{ $periodo }}{{ $filtroTexto ? ' · '.$filtroTexto : '' }} · Generado: {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</div>
</header>
<footer>Control de Asistencia · {{ $empresa }} <span style="float:right">Página <span class="pagenum"></span></span></footer>

@if($tipo === 'kardex' && $empleado)
    <div class="ficha"><strong>{{ $empleado->nombre_completo }}</strong> · {{ implode(' · ', array_filter(['No. '.$empleado->numero_empleado, $empleado->departamento?->nombre, $empleado->puesto?->nombre, 'Horario: '.($empleado->horario?->nombre ?? 'Sin horario')])) }}</div>
    <table class="resumen"><tr>
        @foreach($resumen as $k => $val)<td><div class="v">{{ $val }}</div><div class="k">{{ $k }}</div></td>@endforeach
    </tr></table>
@endif

<table class="datos">
    @include('reportes.tablas.'.$tipo)
</table>

@if($tipo === 'general' && $retardosPorFalta > 0)
    <p style="font-size:7.5px;color:#64748b;margin-top:8px">Cada {{ $retardosPorFalta }} retardos equivalen a 1 falta.</p>
@endif
@if($tipo === 'kardex')
    <table style="width:100%;margin-top:50px;font-size:8px"><tr>
        <td style="width:40%;text-align:center;border-top:1px solid #0f172a;padding-top:4px">Firma del empleado</td><td></td>
        <td style="width:40%;text-align:center;border-top:1px solid #0f172a;padding-top:4px">Recursos Humanos</td>
    </tr></table>
@endif
</body>
</html>
