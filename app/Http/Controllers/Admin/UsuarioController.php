<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Perfil;
use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Gestión de usuarios y perfiles (RF02, HU10). */
class UsuarioController extends Controller
{
    public function index()
    {
        return view('admin.usuarios.index', ['usuarios' => User::with('personal')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.usuarios.form', ['usuario' => new User(['activo' => true]), 'personal' => Personal::activos()->get()]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        User::create($datos);

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        return view('admin.usuarios.form', ['usuario' => $usuario, 'personal' => Personal::activos()->get()]);
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $this->validar($request, $usuario);
        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        abort_if($usuario->is($request->user()) && (! $datos['activo'] || $datos['perfil'] !== Perfil::ADMIN->value), 422, 'No puede desactivarse ni quitarse el perfil de administrador a sí mismo.');
        $usuario->update($datos);

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario)],
            'perfil' => ['required', Rule::enum(Perfil::class)],
            'personal_id' => ['nullable', 'exists:personal,id', Rule::requiredIf($request->input('perfil') === Perfil::CONSULTA->value)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ], ['personal_id.required' => 'Un usuario de consulta debe estar vinculado a una persona del personal.'], ['name' => 'nombre', 'email' => 'correo']);
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }
}
