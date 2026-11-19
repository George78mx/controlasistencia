<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Checada;
use App\Models\DiaFestivo;
use App\Models\Empleado;
use App\Models\Incidencia;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Convierte las checadas de cada empleado en un registro diario de asistencia:
 * asistencia, retardo, falta, justificada, descanso, festivo, pendiente o sin_horario.
 * Soporta turnos nocturnos (salida al día siguiente).
 */
class CalculadoraAsistencia
{
    /** Horas antes de la entrada programada en que ya se acepta una checada (turnos nocturnos). */
    private const ANTICIPACION_HORAS = 4;
    /** Horas después de la salida programada en que se acepta la checada de salida (turnos nocturnos). */
    private const HOLGURA_SALIDA_HORAS = 6;

    private ?array $festivos = null;

    public function calcularRango(CarbonInterface $desde, CarbonInterface $hasta, ?Collection $empleados = null): int
    {
        $desde = Carbon::parse($desde->toDateString());
        $hasta = Carbon::parse(min($hasta->toDateString(), now()->toDateString()));
        if ($hasta < $desde) return 0;

        $empleados ??= Empleado::with('horario.dias')
            ->where(fn ($q) => $q->whereNull('fecha_baja')->orWhere('fecha_baja', '>=', $desde->toDateString()))
            ->get();

        $n = 0;
        foreach ($empleados as $empleado) {
            $empleado->loadMissing('horario.dias');
            $checadas = Checada::where('numero_empleado', $empleado->numero_empleado)
                ->whereBetween('fecha_hora', [$desde->copy()->subDay(), $hasta->copy()->addDays(2)])
                ->orderBy('fecha_hora')->get();
            $incidencias = Incidencia::with('tipo')->where('empleado_id', $empleado->id)
                ->where('fecha_inicio', '<=', $hasta->toDateString())
                ->where('fecha_fin', '>=', $desde->toDateString())->get();

            for ($d = $desde->copy(); $d <= $hasta; $d->addDay()) {
                if ($this->calcularDia($empleado, $d->copy(), $checadas, $incidencias)) $n++;
            }
        }
        return $n;
    }

    public function calcularDia(Empleado $empleado, CarbonInterface $fecha, ?Collection $checadas = null, ?Collection $incidencias = null): ?Asistencia
    {
        $fecha = Carbon::parse($fecha->toDateString());
        $fechaStr = $fecha->toDateString();

        if ($fecha->isFuture() && !$fecha->isToday()) return null;

        if (!$empleado->laboraEn($fecha) || $empleado->trashed()) {
            Asistencia::where('empleado_id', $empleado->id)->where('fecha', $fechaStr)->delete();
            return null;
        }

        $horario = $empleado->horario;
        $dia = $horario?->dia($fecha->isoWeekday());
        $laborable = $dia && $dia->laborable && $dia->entrada && $dia->salida;

        $entProg = $salProg = null;
        if ($laborable) {
            $entProg = Carbon::parse("$fechaStr {$dia->entrada}");
            $salProg = Carbon::parse("$fechaStr {$dia->salida}");
            if ($dia->cruzaMedianoche()) $salProg->addDay();
        }

        // Ventana en la que se buscan checadas para este día
        [$ini, $fin] = $this->ventana($empleado, $fecha, $entProg, $salProg, $dia?->cruzaMedianoche() ?? false);

        $checadasDia = ($checadas ?? Checada::where('numero_empleado', $empleado->numero_empleado)
                ->whereBetween('fecha_hora', [$ini, $fin])->orderBy('fecha_hora')->get())
            ->filter(fn ($c) => $c->fecha_hora >= $ini && $c->fecha_hora <= $fin)
            ->sortBy('fecha_hora')->values();

        $entrada = $checadasDia->first()?->fecha_hora;
        $ultima = $checadasDia->last()?->fecha_hora;
        $minimo = Setting::int('checada_minima_minutos', 5);
        $salida = ($entrada && $ultima && $entrada->diffInMinutes($ultima) >= $minimo) ? $ultima : null;

        $incidencia = ($incidencias ?? Incidencia::with('tipo')->where('empleado_id', $empleado->id)
                ->where('fecha_inicio', '<=', $fechaStr)->where('fecha_fin', '>=', $fechaStr)->get())
            ->first(fn ($i) => $i->fecha_inicio->toDateString() <= $fechaStr && $i->fecha_fin->toDateString() >= $fechaStr);

        $datos = [
            'horario_id' => $horario?->id,
            'entrada_programada' => $entProg,
            'salida_programada' => $salProg,
            'entrada' => $entrada,
            'salida' => $salida,
            'num_checadas' => $checadasDia->count(),
            'minutos_retardo' => 0,
            'minutos_salida_anticipada' => 0,
            'minutos_trabajados' => ($entrada && $salida) ? (int) $entrada->diffInMinutes($salida) : 0,
            'falta_por_retardo' => false,
            'incidencia_id' => $incidencia?->id,
            'observaciones' => null,
        ];

        $festivo = $this->esFestivo($fechaStr);

        if (!$horario) {
            $datos['estatus'] = 'sin_horario';
        } elseif ($festivo) {
            $datos['estatus'] = 'festivo';
            $datos['observaciones'] = $festivo.($entrada ? ' · laboró' : '');
        } elseif ($incidencia) {
            $datos['estatus'] = 'justificada';
            $datos['observaciones'] = $incidencia->tipo?->nombre;
        } elseif (!$laborable) {
            $datos['estatus'] = 'descanso';
            if ($entrada) $datos['observaciones'] = 'Laboró en día de descanso';
        } elseif (!$entrada) {
            $limite = $entProg->copy()->addMinutes(max($horario->minutos_para_falta, $horario->tolerancia_minutos));
            $datos['estatus'] = now() < $limite ? 'pendiente' : 'falta';
        } else {
            $retardo = $entrada > $entProg ? (int) $entProg->diffInMinutes($entrada) : 0;
            if ($retardo > $horario->tolerancia_minutos) {
                $datos['minutos_retardo'] = $retardo;
                if ($horario->minutos_para_falta > 0 && $retardo >= $horario->minutos_para_falta) {
                    $datos['estatus'] = 'falta';
                    $datos['falta_por_retardo'] = true;
                } else {
                    $datos['estatus'] = 'retardo';
                }
            } else {
                $datos['estatus'] = 'asistencia';
            }

            if ($salida && $salida < $salProg) {
                $datos['minutos_salida_anticipada'] = (int) $salida->diffInMinutes($salProg);
            }
            if (!$salida && now() > $salProg->copy()->addHours(2)) {
                $datos['observaciones'] = 'Sin registro de salida';
            }
        }

        return Asistencia::updateOrCreate(['empleado_id' => $empleado->id, 'fecha' => $fechaStr], $datos);
    }

    private function ventana(Empleado $empleado, Carbon $fecha, ?Carbon $entProg, ?Carbon $salProg, bool $nocturno): array
    {
        if ($nocturno && $entProg && $salProg) {
            return [$entProg->copy()->subHours(self::ANTICIPACION_HORAS), $salProg->copy()->addHours(self::HOLGURA_SALIDA_HORAS)];
        }

        $ini = $fecha->copy()->startOfDay();
        // Si el día anterior fue turno nocturno, sus checadas de madrugada no cuentan para hoy
        $ayer = $empleado->horario?->dia($fecha->copy()->subDay()->isoWeekday());
        if ($ayer && $ayer->laborable && $ayer->cruzaMedianoche()) {
            $finAyer = Carbon::parse($fecha->toDateString().' '.$ayer->salida)->addHours(self::HOLGURA_SALIDA_HORAS);
            if ($entProg) $finAyer = $finAyer->min($entProg->copy()->subHours(self::ANTICIPACION_HORAS));
            $ini = $finAyer->max($ini)->addSecond();
        }
        return [$ini, $fecha->copy()->endOfDay()];
    }

    private function esFestivo(string $fecha): ?string
    {
        $this->festivos ??= DiaFestivo::pluck('descripcion', 'fecha')
            ->mapWithKeys(fn ($d, $f) => [Carbon::parse($f)->toDateString() => $d])->all();
        return $this->festivos[$fecha] ?? null;
    }
}
