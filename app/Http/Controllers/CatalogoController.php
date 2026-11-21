<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\DiaFestivo;
use App\Models\Puesto;
use App\Models\Sucursal;
use App\Models\TipoIncidencia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Catálogos sencillos: departamentos, puestos, días festivos y tipos de incidencia. */
class CatalogoController extends Controller
{
    private const CATALOGOS = [
        'sucursales' => ['modelo' => Sucursal::class, 'titulo' => 'Sucursales', 'singular' => 'sucursal', 'icono' => 'shop'],
        'departamentos' => ['modelo' => Departamento::class, 'titulo' => 'Departamentos', 'singular' => 'departamento', 'icono' => 'diagram-3'],
        'puestos' => ['modelo' => Puesto::class, 'titulo' => 'Puestos', 'singular' => 'puesto', 'icono' => 'briefcase'],
        'festivos' => ['modelo' => DiaFestivo::class, 'titulo' => 'Días festivos', 'singular' => 'día festivo', 'icono' => 'calendar-heart'],
        'tipos-incidencia' => ['modelo' => TipoIncidencia::class, 'titulo' => 'Tipos de incidencia', 'singular' => 'tipo de incidencia', 'icono' => 'tags'],
    ];

    private function cfg(string $catalogo): array
    {
        abort_unless(isset(self::CATALOGOS[$catalogo]), 404);
        return self::CATALOGOS[$catalogo] + ['clave' => $catalogo];
    }

    public function index(string $catalogo)
    {
        $cfg = $this->cfg($catalogo);
        $q = $cfg['modelo']::query();
        $registros = $catalogo === 'festivos' ? $q->orderByDesc('fecha')->get() : $q->orderBy('nombre')->get();
        return view('catalogos.index', compact('cfg', 'registros'));
    }

    public function store(Request $request, string $catalogo)
    {
        $cfg = $this->cfg($catalogo);
        $cfg['modelo']::create($this->validar($request, $catalogo));
        return back()->with('ok', 'Registro agregado.');
    }

    public function update(Request $request, string $catalogo, int $id)
    {
        $cfg = $this->cfg($catalogo);
        $registro = $cfg['modelo']::findOrFail($id);
        $registro->update($this->validar($request, $catalogo, $id));
        return back()->with('ok', 'Registro actualizado.');
    }

    public function destroy(string $catalogo, int $id)
    {
        $cfg = $this->cfg($catalogo);
        $registro = $cfg['modelo']::findOrFail($id);
        try {
            if (method_exists($registro, 'empleados') && $registro->empleados()->exists()) {
                return back()->with('error', 'No se puede eliminar: tiene empleados asignados. Puedes desactivarlo.');
            }
            if (method_exists($registro, 'equipos') && $registro->equipos()->exists()) {
                return back()->with('error', 'No se puede eliminar: tiene equipos asignados. Puedes desactivarla.');
            }
            if (method_exists($registro, 'incidencias') && $registro->incidencias()->exists()) {
                return back()->with('error', 'No se puede eliminar: tiene incidencias registradas. Puedes desactivarlo.');
            }
            $registro->delete();
        } catch (\Illuminate\Database\QueryException) {
            return back()->with('error', 'No se puede eliminar porque está en uso.');
        }
        return back()->with('ok', 'Registro eliminado.');
    }

    private function validar(Request $request, string $catalogo, ?int $id = null): array
    {
        return match ($catalogo) {
            'festivos' => $request->validate([
                'fecha' => ['required', 'date', Rule::unique('dias_festivos')->ignore($id)],
                'descripcion' => ['required', 'string', 'max:150'],
            ]),
            'tipos-incidencia' => $request->validate([
                'clave' => ['required', 'string', 'max:10', Rule::unique('tipos_incidencia')->ignore($id)],
                'nombre' => ['required', 'string', 'max:100'],
                'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ]) + ['con_goce' => $request->boolean('con_goce'), 'activo' => $request->boolean('activo')],
            default => $request->validate([
                'nombre' => ['required', 'string', 'max:100', Rule::unique($catalogo)->ignore($id)],
                'descripcion' => ['nullable', 'string', 'max:255'],
            ]) + ['activo' => $request->boolean('activo')],
        };
    }
}
