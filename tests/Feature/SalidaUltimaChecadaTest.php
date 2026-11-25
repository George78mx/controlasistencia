<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Checada;
use App\Models\Device;
use App\Models\Empleado;
use App\Models\Horario;
use App\Services\CalculadoraAsistencia;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalidaUltimaChecadaTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;
    private Device $equipo;

    private function preparar(string $entrada = '09:00', string $salida = '18:00'): void
    {
        $h = Horario::create(['nombre' => 'Prueba', 'tolerancia_minutos' => 10, 'minutos_para_falta' => 30]);
        foreach (range(1, 7) as $d) {
            $h->dias()->create(['dia_semana' => $d, 'laborable' => $d <= 5, 'entrada' => $entrada, 'salida' => $salida]);
        }
        $this->equipo = Device::create(['nombre' => 'E', 'ip' => '127.0.0.1', 'usuario' => 'x', 'password' => 'x']);
        $this->empleado = Empleado::create(['numero_empleado' => '1', 'nombre' => 'Uno', 'horario_id' => $h->id, 'activo' => true]);
    }

    private function calcular(string $ahora, array $checadas, string $fecha = '2026-11-24'): Asistencia
    {
        Carbon::setTestNow($ahora);
        foreach ($checadas as $c) {
            Checada::create(['device_id' => $this->equipo->id, 'numero_empleado' => '1', 'empleado_id' => $this->empleado->id, 'fecha_hora' => $c, 'tipo' => 'checkOut']);
        }
        Asistencia::query()->delete(); // SQLite guarda DATE con hora y updateOrCreate no lo encuentra; en MySQL sí
        return app(CalculadoraAsistencia::class)->calcularDia(Empleado::find($this->empleado->id), Carbon::parse($fecha));
    }

    public function test_checada_intermedia_no_es_salida_y_la_ultima_si(): void
    {
        $this->preparar();
        $a = $this->calcular('2026-11-24 10:10', ['2026-11-24 09:05', '2026-11-24 10:05']);
        $this->assertSame('09:05', $a->entrada->format('H:i'));
        $this->assertNull($a->salida);
        $this->assertSame('asistencia', $a->estatus);
        $this->assertEquals(0, $a->minutos_trabajados);

        // Ya pasó la hora de salida pero sigue sin checar: 10:05 tampoco cuenta
        $this->assertNull($this->calcular('2026-11-24 19:00', [])->salida);

        $a = $this->calcular('2026-11-24 19:10', ['2026-11-24 18:03']);
        $this->assertSame('18:03', $a->salida->format('H:i'));
        $a = $this->calcular('2026-11-24 19:30', ['2026-11-24 19:25']);
        $this->assertSame('19:25', $a->salida->format('H:i'));
        $this->assertEquals(620, $a->minutos_trabajados);
    }

    public function test_al_cerrar_el_dia_toma_la_ultima_y_avisa_si_es_muy_temprana(): void
    {
        $this->preparar();
        $a = $this->calcular('2026-11-25 00:15', ['2026-11-24 09:05', '2026-11-24 10:05']);
        $this->assertSame('10:05', $a->salida->format('H:i'));
        $this->assertStringContainsString('verificar', $a->observaciones);
        $this->assertEquals(475, $a->minutos_salida_anticipada);
    }

    public function test_acepta_salida_dentro_de_la_ventana(): void
    {
        $this->preparar();
        $a = $this->calcular('2026-11-24 17:30', ['2026-11-24 09:00', '2026-11-24 17:00']);
        $this->assertSame('17:00', $a->salida->format('H:i'));
        $this->assertNull($a->observaciones);
    }

    public function test_turno_nocturno(): void
    {
        $this->preparar('22:00', '06:00');
        $this->assertNull($this->calcular('2026-11-24 23:30', ['2026-11-24 22:00', '2026-11-24 23:00'])->salida);
        $a = $this->calcular('2026-11-25 06:20', ['2026-11-25 06:05']);
        $this->assertSame('2026-11-25 06:05', $a->salida->format('Y-m-d H:i'));
    }

    public function test_dia_de_descanso_espera_al_cierre(): void
    {
        $this->preparar();
        $this->assertNull($this->calcular('2026-11-22 12:00', ['2026-11-22 09:00', '2026-11-22 10:00'], '2026-11-22')->salida);
        $this->assertSame('10:00', $this->calcular('2026-11-23 00:15', [], '2026-11-22')->salida->format('H:i'));
    }
}
