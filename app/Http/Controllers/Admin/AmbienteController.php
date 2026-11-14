<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ambiente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AmbienteController extends Controller
{
    public function index()
    {
        return view('admin.ambientes.index', ['ambientes' => Ambiente::withCount('bienes')->orderBy('nombre')->get()]);
    }

    public function create()
    {
        return view('admin.ambientes.form', ['ambiente' => new Ambiente(['activo' => true])]);
    }

    public function store(Request $request)
    {
        Ambiente::create($this->validar($request));

        return redirect()->route('admin.ambientes.index')->with('ok', 'Ambiente creado.');
    }

    public function edit(Ambiente $ambiente)
    {
        return view('admin.ambientes.form', compact('ambiente'));
    }

    public function update(Request $request, Ambiente $ambiente)
    {
        $ambiente->update($this->validar($request, $ambiente));

        return redirect()->route('admin.ambientes.index')->with('ok', 'Ambiente actualizado.');
    }

    private function validar(Request $request, ?Ambiente $ambiente = null): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('ambientes')->ignore($ambiente)],
            'tipo' => ['required', Rule::in(array_keys(Ambiente::TIPOS))],
            'piso' => ['nullable', 'string', 'max:20'],
        ]);
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }
}
