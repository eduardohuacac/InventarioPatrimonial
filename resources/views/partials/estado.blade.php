@php($n = is_string($e) ? $e : $e?->nombre)
<span class="etq {{ ['bueno' => 'ok', 'regular' => 'warn', 'malo' => 'danger'][$n] ?? 'muted' }}">{{ ucfirst((string) $n) }}</span>
