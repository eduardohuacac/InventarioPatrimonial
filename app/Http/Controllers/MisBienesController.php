<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use Illuminate\Http\Request;

/** Vista «Mis bienes» (RF12, HU07): solo los bienes a cargo del usuario. */
class MisBienesController extends Controller
{
    public function __invoke(Request $request)
    {
        $personal = $request->user()->personal;

        $bienes = $personal
            ? Bien::with(['ambiente', 'estado'])->where('responsable_actual_id', $personal->id)
                ->buscar($request->query('q'))->orderBy('codigo_patrimonial')->paginate(20)->withQueryString()
            : null;

        return view('mis-bienes.index', compact('personal', 'bienes'));
    }
}
