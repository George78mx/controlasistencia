<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('perfil', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password_actual' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $user->name = $data['name'];
        if (!empty($data['password'])) $user->password = $data['password'];
        $user->save();
        return back()->with('ok', 'Perfil actualizado.');
    }
}
