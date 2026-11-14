<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Responsables de bienes. Datos personales mínimos (Ley N.° 29733). */
class PersonalController extends Controller
{
    public function index()
    {
        return view('admin.personal.index', ['personal' => Personal::withCount('bienes')->orderBy('nombres')->get()]);
    }

    public function create()
    {
        return view('admin.personal.form', ['persona' => new Personal(['activo' => true])]);
    }

    public function store(Request $request)
    {
        Personal::create($this->validar($request));

        return redirect()->route('admin.personal.index')->with('ok', 'Persona registrada.');
    }

    public function edit(Personal $personal)
    {
        return view('admin.personal.form', ['persona' => $personal]);
    }

    public function update(Request $request, Personal $personal)
    {
        $datos = $this->validar($request, $personal);
        if (! $datos['activo'] && $personal->activo && $personal->bienes()->where('situacion', '!=', 'dado_de_baja')->exists()) {
            return back()->withInput()->withErrors(['activo' => 'Antes de desactivar a esta persona, reasigne los bienes a su cargo.']);
        }
        $personal->update($datos);

        return redirect()->route('admin.personal.index')->with('ok', 'Datos actualizados.');
    }

    private function validar(Request $request, ?Personal $personal = null): array
    {
        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:150'],
            'cargo' => ['required', 'string', 'max:100'],
            'dni' => ['nullable', 'digits:8', Rule::unique('personal', 'dni')->ignore($personal)],
        ]);
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }
}
