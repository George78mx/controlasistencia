<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => User::with('roles')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('usuarios.form', ['usuario' => new User(['activo' => true]), 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $usuario = User::create($data);
        $usuario->syncRoles($request->input('roles', []));
        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        return view('usuarios.form', ['usuario' => $usuario, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $usuario)
    {
        $data = $this->validar($request, $usuario);
        if (empty($data['password'])) unset($data['password']);
        if ($usuario->id === auth()->id()) {
            $data['activo'] = true; // no desactivarse a sí mismo
        }
        $usuario->update($data);
        $roles = $request->input('roles', []);
        if ($usuario->id === auth()->id() && $usuario->hasRole('Administrador') && !in_array('Administrador', $roles)) {
            $roles[] = 'Administrador'; // evita quedarse sin acceso
        }
        $usuario->syncRoles($roles);
        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) return back()->with('error', 'No puedes eliminar tu propio usuario.');
        $usuario->delete();
        return back()->with('ok', 'Usuario eliminado.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
        ]);
        unset($data['roles']);
        $data['activo'] = $request->boolean('activo');
        return $data;
    }
}
