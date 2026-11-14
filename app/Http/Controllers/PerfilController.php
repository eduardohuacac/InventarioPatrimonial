<?php

namespace App\Http\Controllers;

use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    public function editar()
    {
        return view('perfil.clave');
    }

    public function actualizar(Request $request)
    {
        $request->validate([
            'actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], ['actual.current_password' => 'La contraseña actual no es correcta.'], ['password' => 'nueva contraseña']);

        $request->user()->forceFill(['password' => $request->password])->saveQuietly();
        Auditor::registrar('cambiar_clave', $request->user());

        return redirect()->route('panel')->with('ok', 'Contraseña actualizada.');
    }
}
