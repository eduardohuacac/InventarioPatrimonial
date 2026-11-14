<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Inicio y cierre de sesión (RF01) con limitación de intentos (OWASP A07). */
class LoginController extends Controller
{
    private const INTENTOS = 5;

    public function mostrar()
    {
        return view('auth.login');
    }

    public function ingresar(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:100'],
        ], [], ['email' => 'correo', 'password' => 'contraseña']);

        $clave = Str::lower($credenciales['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Vuelva a intentarlo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }

        if (! Auth::attempt([...$credenciales, 'activo' => true])) {
            RateLimiter::hit($clave, 60);
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();
        $request->user()->forceFill(['ultimo_acceso' => now()])->saveQuietly();

        return redirect()->intended(route('panel'));
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Sesión cerrada.');
    }
}
