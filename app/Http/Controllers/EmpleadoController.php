<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Device;
use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Puesto;
use App\Models\Sucursal;
use App\Services\CalculadoraAsistencia;
use App\Support\SucursalActiva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $empleados = Empleado::with(['departamento', 'puesto', 'horario', 'device', 'sucursal'])
            ->buscar($request->q)
            ->deSucursal(SucursalActiva::id(), $request->integer('device_id') ?: null)
            ->when($request->boolean('sin_sucursal'), fn ($q) => $q->whereNull('sucursal_id'))
            ->when($request->departamento_id, fn ($q, $v) => $q->where('departamento_id', $v))
            ->when($request->estado !== 'todos', fn ($q) => $q->where('activo', $request->estado !== 'inactivos'))
            ->orderBy('nombre')->orderBy('apellido_paterno')
            ->paginate(20)->withQueryString();

        return view('empleados.index', $this->catalogos() + compact('empleados'));
    }

    public function show(Request $request, Empleado $empleado)
    {
        $desde = Carbon::parse($request->desde ?: now()->startOfMonth()->toDateString());
        $hasta = Carbon::parse($request->hasta ?: now()->toDateString());
        $asistencias = Asistencia::with('incidencia.tipo')->where('empleado_id', $empleado->id)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->orderByDesc('fecha')->get();

        return view('empleados.show', [
            'empleado' => $empleado->load(['departamento', 'puesto', 'horario.dias', 'device', 'sucursal']),
            'asistencias' => $asistencias,
            'resumen' => $asistencias->countBy('estatus'),
            'desde' => $desde, 'hasta' => $hasta,
            'checadas' => $empleado->checadas()->with('device')->latest('fecha_hora')->limit(20)->get(),
            'incidencias' => $empleado->incidencias()->with('tipo')->latest('fecha_inicio')->limit(10)->get(),
        ]);
    }

    public function create()
    {
        return view('empleados.form', $this->catalogos() + ['empleado' => new Empleado(['activo' => true, 'fecha_ingreso' => now(), 'sucursal_id' => SucursalActiva::id()])]);
    }

    public function store(Request $request, CalculadoraAsistencia $calc)
    {
        $empleado = Empleado::create($this->validar($request));
        $this->recalcular($empleado, $calc);
        return redirect()->route('empleados.show', $empleado)->with('ok', 'Empleado registrado.');
    }

    public function edit(Empleado $empleado)
    {
        return view('empleados.form', $this->catalogos() + compact('empleado'));
    }

    public function update(Request $request, Empleado $empleado, CalculadoraAsistencia $calc)
    {
        $data = $this->validar($request, $empleado);
        $cambioHorario = $empleado->horario_id != ($data['horario_id'] ?? null);
        $empleado->update($data);
        if ($cambioHorario) $this->recalcular($empleado->fresh(), $calc);
        return redirect()->route('empleados.show', $empleado)->with('ok', 'Empleado actualizado.');
    }

    public function destroy(Empleado $empleado)
    {
        $empleado->update(['activo' => false, 'fecha_baja' => $empleado->fecha_baja ?? now()]);
        $empleado->delete();
        return redirect()->route('empleados.index')->with('ok', 'Empleado eliminado (se conserva su historial).');
    }

    private function recalcular(Empleado $empleado, CalculadoraAsistencia $calc): void
    {
        $desde = $empleado->fecha_ingreso && $empleado->fecha_ingreso > now()->subDays(31) ? $empleado->fecha_ingreso : now()->subDays(31);
        $calc->calcularRango($desde, now(), collect([$empleado]));
    }

    private function catalogos(): array
    {
        return [
            'departamentos' => Departamento::where('activo', true)->orderBy('nombre')->get(),
            'puestos' => Puesto::where('activo', true)->orderBy('nombre')->get(),
            'horarios' => Horario::where('activo', true)->orderBy('nombre')->get(),
            'equipos' => Device::with('sucursal')->orderBy('nombre')->get(),
            'sucursales' => Sucursal::where('activo', true)->orderBy('nombre')->get(),
        ];
    }

    private function validar(Request $request, ?Empleado $empleado = null): array
    {
        $data = $request->validate([
            'numero_empleado' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('empleados')->ignore($empleado?->id)],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['nullable', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13'],
            'curp' => ['nullable', 'string', 'size:18'],
            'nss' => ['nullable', 'digits:11'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'genero' => ['nullable', 'in:M,F,X'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'departamento_id' => ['nullable', 'exists:departamentos,id'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'puesto_id' => ['nullable', 'exists:puestos,id'],
            'horario_id' => ['nullable', 'exists:horarios,id'],
            'device_id' => ['nullable', 'exists:devices,id'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_baja' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'contacto_emergencia' => ['nullable', 'string', 'max:150'],
            'telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ], [
            'numero_empleado.regex' => 'El número de empleado solo puede tener letras, números, guion y guion bajo.',
        ], ['numero_empleado' => 'número de empleado']);

        foreach (['rfc', 'curp'] as $c) if (!empty($data[$c])) $data[$c] = mb_strtoupper($data[$c]);
        $data['activo'] = $request->boolean('activo');
        // Si no se eligió sucursal, toma la del equipo principal
        if (empty($data['sucursal_id']) && !empty($data['device_id'])) {
            $data['sucursal_id'] = Device::whereKey($data['device_id'])->value('sucursal_id');
        }

        if ($request->hasFile('foto')) {
            if ($empleado?->foto) Storage::disk('public')->delete($empleado->foto);
            $data['foto'] = $request->file('foto')->store('empleados', 'public');
        } else {
            unset($data['foto']);
        }
        return $data;
    }
}
