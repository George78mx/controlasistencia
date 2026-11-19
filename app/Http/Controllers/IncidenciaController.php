<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Incidencia;
use App\Models\TipoIncidencia;
use App\Services\CalculadoraAsistencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidenciaController extends Controller
{
    public function index(Request $request)
    {
        $incidencias = Incidencia::with(['empleado', 'tipo', 'usuario'])
            ->when($request->tipo_incidencia_id, fn ($q, $v) => $q->where('tipo_incidencia_id', $v))
            ->when($request->q, fn ($q, $v) => $q->whereHas('empleado', fn ($q) => $q->buscar($v)))
            ->when($request->desde, fn ($q, $v) => $q->where('fecha_fin', '>=', $v))
            ->when($request->hasta, fn ($q, $v) => $q->where('fecha_inicio', '<=', $v))
            ->latest('fecha_inicio')->paginate(20)->withQueryString();

        return view('incidencias.index', ['incidencias' => $incidencias, 'tipos' => TipoIncidencia::orderBy('nombre')->get()]);
    }

    public function create(Request $request)
    {
        return view('incidencias.form', $this->catalogos() + ['incidencia' => new Incidencia([
            'empleado_id' => $request->empleado_id,
            'fecha_inicio' => $request->fecha ?: now()->toDateString(),
            'fecha_fin' => $request->fecha ?: now()->toDateString(),
        ])]);
    }

    public function store(Request $request, CalculadoraAsistencia $calc)
    {
        $data = $this->validar($request);
        $data['user_id'] = auth()->id();
        $incidencia = Incidencia::create($data);
        $this->recalcular($incidencia, $calc);
        return redirect()->route('incidencias.index')->with('ok', 'Incidencia registrada y asistencia recalculada.');
    }

    public function edit(Incidencia $incidencia)
    {
        return view('incidencias.form', $this->catalogos() + compact('incidencia'));
    }

    public function update(Request $request, Incidencia $incidencia, CalculadoraAsistencia $calc)
    {
        $antes = clone $incidencia;
        $incidencia->update($this->validar($request, $incidencia));
        $this->recalcular($antes, $calc);
        $this->recalcular($incidencia->fresh(), $calc);
        return redirect()->route('incidencias.index')->with('ok', 'Incidencia actualizada.');
    }

    public function destroy(Incidencia $incidencia, CalculadoraAsistencia $calc)
    {
        $copia = clone $incidencia;
        if ($incidencia->documento) Storage::delete($incidencia->documento);
        $incidencia->delete();
        $this->recalcular($copia, $calc);
        return back()->with('ok', 'Incidencia eliminada y asistencia recalculada.');
    }

    public function documento(Incidencia $incidencia)
    {
        abort_unless($incidencia->documento && Storage::exists($incidencia->documento), 404);
        return Storage::download($incidencia->documento);
    }

    private function recalcular(Incidencia $i, CalculadoraAsistencia $calc): void
    {
        $empleado = Empleado::withTrashed()->with('horario.dias')->find($i->empleado_id);
        if ($empleado) $calc->calcularRango(Carbon::parse($i->fecha_inicio), Carbon::parse($i->fecha_fin), collect([$empleado]));
    }

    private function catalogos(): array
    {
        return [
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'tipos' => TipoIncidencia::where('activo', true)->orderBy('nombre')->get(),
        ];
    }

    private function validar(Request $request, ?Incidencia $incidencia = null): array
    {
        $data = $request->validate([
            'empleado_id' => ['required', 'exists:empleados,id'],
            'tipo_incidencia_id' => ['required', 'exists:tipos_incidencia,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'folio' => ['nullable', 'string', 'max:50'],
            'motivo' => ['nullable', 'string', 'max:2000'],
            'documento' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        if ($request->hasFile('documento')) {
            if ($incidencia?->documento) Storage::delete($incidencia->documento);
            $data['documento'] = $request->file('documento')->store('incidencias');
        } else {
            unset($data['documento']);
        }
        return $data;
    }
}
