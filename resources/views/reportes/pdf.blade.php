<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2933; }
    h1 { font-size: 14px; margin: 0; } h2 { font-size: 11px; margin: 12px 0 4px; color: #1e3a5f; }
    .sub { color: #52606d; margin: 2px 0 8px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #cbd2d9; padding: 3px 4px; text-align: left; }
    th { background: #e8eef7; }
    .pie { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 8px; color: #616e7c; }
</style>
</head>
<body>
    <h1>I.E. Pública N.° 50023 «República de México» – Cusco</h1>
    <div><b>{{ $reporte['titulo'] }}</b></div>
    <div class="sub">{{ $reporte['subtitulo'] }}<br>Generado el {{ $reporte['generado']->format('d/m/Y H:i') }} · Total: {{ $reporte['total'] }} bienes</div>
    @forelse ($reporte['grupos'] as $grupo => $filas)
        <h2>{{ $grupo }} ({{ count($filas) }})</h2>
        <table>
            <thead><tr>@foreach($reporte['columnas'] as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
            <tbody>@foreach($filas as $f)<tr>@foreach($f as $v)<td>{{ $v }}</td>@endforeach</tr>@endforeach</tbody>
        </table>
    @empty
        <p>No hay bienes que cumplan los criterios.</p>
    @endforelse
    <div class="pie">Herramienta auxiliar de control interno. No reemplaza el registro oficial en el SIGA – Módulo de Patrimonio.</div>
</body>
</html>
