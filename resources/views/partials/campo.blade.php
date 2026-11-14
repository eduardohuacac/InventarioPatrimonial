{{-- Campo de formulario accesible. Parámetros: nombre, etiqueta, tipo, valor, req, ayuda, opciones (array id => texto), vacio --}}
@php($tipo = $tipo ?? 'text')
@php($id = 'f-'.$nombre)
<div @class(['campo', 'error' => $errors->has($nombre), $clase ?? null])>
    <label for="{{ $id }}">{{ $etiqueta }} @if($req ?? false)<span class="req" aria-hidden="true">*</span>@endif</label>
    @if ($tipo === 'select')
        <select id="{{ $id }}" name="{{ $nombre }}" @if($req ?? false) required @endif @if($errors->has($nombre)) aria-invalid="true" aria-describedby="{{ $id }}-err" @endif>
            <option value="">{{ $vacio ?? '— Seleccione —' }}</option>
            @foreach ($opciones as $valorOpcion => $texto)
                <option value="{{ $valorOpcion }}" @selected((string) old($nombre, $valor ?? '') === (string) $valorOpcion)>{{ $texto }}</option>
            @endforeach
        </select>
    @elseif ($tipo === 'textarea')
        <textarea id="{{ $id }}" name="{{ $nombre }}" @if($req ?? false) required @endif>{{ old($nombre, $valor ?? '') }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $tipo }}" name="{{ $nombre }}" value="{{ old($nombre, $valor ?? '') }}" @if($req ?? false) required @endif
               @isset($max) max="{{ $max }}" @endisset @isset($maxlength) maxlength="{{ $maxlength }}" @endisset
               @if($errors->has($nombre)) aria-invalid="true" aria-describedby="{{ $id }}-err" @endif>
    @endif
    @isset($ayuda)<div class="ayuda">{{ $ayuda }}</div>@endisset
    @error($nombre)<div class="msg-error" id="{{ $id }}-err">{{ $message }}</div>@enderror
</div>
