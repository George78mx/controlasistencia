<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    public function index()
    {
        return view('horarios.index', ['horarios' => Horario::with('dias')->withCount('empleados')->orderBy('nombre')->get()]);
    }

    public function create()
    {
        $horario = new Horario(['tolerancia_minutos' => 10, 'minutos_para_falta' => 30, 'activo' => true]);
        return view('horarios.form', compact('horario'));
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $h = Horario::create($this->validar($request));
            $this->guardarDias($h, $request);
        });
        return redirect()->route('horarios.index')->with('ok', 'Horario creado.');
    }

    public function edit(Horario $horario)
    {
        $horario->load('dias');
        return view('horarios.form', compact('horario'));
    }

    public function update(Request $request, Horario $horario)
    {
        DB::transaction(function () use ($request, $horario) {
            $horario->update($this->validar($request));
            $this->guardarDias($horario, $request);
        });
        return redirect()->route('horarios.index')->with('ok', 'Horario actualizado. Usa "Recalcular" en Asistencia si quieres aplicarlo a días anteriores.');
    }

    public function destroy(Horario $horario)
    {
        if ($horario->empleados()->exists()) {
            return back()->with('error', 'No se puede eliminar: hay empleados con este horario.');
        }
        $horario->delete();
        return back()->with('ok', 'Horario eliminado.');
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'tolerancia_minutos' => ['required', 'integer', 'min:0', 'max:240'],
            'minutos_para_falta' => ['required', 'integer', 'min:0', 'max:600'],
            'dias' => ['array'],
            'dias.*.entrada' => ['nullable', 'date_format:H:i'],
            'dias.*.salida' => ['nullable', 'date_format:H:i'],
        ]);
        foreach ($request->input('dias', []) as $n => $d) {
            if (!empty($d['laborable']) && (empty($d['entrada']) || empty($d['salida']))) {
                throw \Illuminate\Validation\ValidationException::withMessages(["dias.$n.entrada" => 'Indica entrada y salida para '.Horario::DIAS[$n].'.']);
            }
        }
        unset($data['dias']);
        $data['activo'] = $request->boolean('activo');
        return $data;
    }

    private function guardarDias(Horario $h, Request $request): void
    {
        foreach (Horario::DIAS as $n => $nombre) {
            $d = $request->input("dias.$n", []);
            $lab = !empty($d['laborable']);
            $h->dias()->updateOrCreate(['dia_semana' => $n], [
                'laborable' => $lab,
                'entrada' => $lab ? $d['entrada'] : null,
                'salida' => $lab ? $d['salida'] : null,
            ]);
        }
    }
}
