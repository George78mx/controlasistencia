<?php

namespace App\Http\Controllers;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return view('roles.index', ['roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('roles.form', ['role' => new Role(), 'permisos' => $this->permisos()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:roles,name'], 'permisos' => ['array']]);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permisos'] ?? []);
        return redirect()->route('roles.index')->with('ok', 'Rol creado.');
    }

    public function edit(Role $role)
    {
        return view('roles.form', ['role' => $role->load('permissions'), 'permisos' => $this->permisos()]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)], 'permisos' => ['array']]);
        if ($role->name !== 'Administrador') $role->name = $data['name'];
        $role->save();
        $role->syncPermissions($role->name === 'Administrador' ? Permission::all() : ($data['permisos'] ?? []));
        return redirect()->route('roles.index')->with('ok', 'Rol actualizado.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Administrador') return back()->with('error', 'El rol Administrador no se puede eliminar.');
        if ($role->users()->exists()) return back()->with('error', 'El rol tiene usuarios asignados.');
        $role->delete();
        return back()->with('ok', 'Rol eliminado.');
    }

    /** Permisos agrupados por módulo con su descripción. */
    private function permisos(): array
    {
        $desc = DatabaseSeeder::PERMISOS;
        return Permission::orderBy('name')->get()
            ->groupBy(fn ($p) => ucfirst(explode('.', $p->name)[0]))
            ->map(fn ($g) => $g->map(fn ($p) => ['name' => $p->name, 'desc' => $desc[$p->name] ?? $p->name]))
            ->all();
    }
}
