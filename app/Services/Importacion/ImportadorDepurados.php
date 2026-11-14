<?php

namespace App\Services\Importacion;

use App\Enums\Situacion;
use App\Exceptions\ReglaNegocioException;
use App\Models\BitacoraMigracion;
use App\Models\LoteImportacion;
use App\Models\User;
use App\Services\RegistroBienService;
use App\Support\Auditor;
use Illuminate\Support\Facades\DB;

/**
 * Carga los registros depurados (RF09, HU08): crea los bienes, guarda el
 * identificador de origen y la bitácora, y verifica la reconciliación:
 *   Registros cargados = Filas − Excluidas − (Fusionadas − Grupos).
 */
class ImportadorDepurados
{
    public function __construct(
        private readonly DiagnosticoDepuracion $diagnostico,
        private readonly RegistroBienService $registroBien,
    ) {}

    public function importar(array $filas, string $nombreArchivo, string $hash, User $usuario): LoteImportacion
    {
        if (LoteImportacion::where('archivo_hash', $hash)->exists()) {
            throw ReglaNegocioException::en('archivo', 'Este archivo ya fue importado anteriormente.');
        }

        $diag = $this->diagnostico->diagnosticar($filas);
        $conErrores = array_filter($diag, fn ($f) => $f['errores'] !== []);
        if ($conErrores) {
            throw ReglaNegocioException::en('archivo', count($conErrores).' fila(s) tienen observaciones bloqueantes. Corríjalas antes de importar.');
        }
        $resumen = $this->diagnostico->resumen($diag);

        return DB::transaction(function () use ($diag, $resumen, $nombreArchivo, $hash, $usuario) {
            $lote = LoteImportacion::create([
                'archivo_nombre' => $nombreArchivo,
                'archivo_hash' => $hash,
                'filas_origen' => $resumen['filas_origen'],
                'conservadas' => $resumen['conservadas'],
                'corregidas' => $resumen['corregidas'],
                'fusionadas' => $resumen['fusionadas'],
                'grupos_fusion' => $resumen['grupos_fusion'],
                'pendientes' => $resumen['pendientes'],
                'excluidas' => $resumen['excluidas'],
                'cargados' => count(array_filter($diag, fn ($f) => $f['carga'])),
                'importado_por' => $usuario->id,
                'importado_en' => now(),
            ]);

            $bienesPorGrupo = [];
            $cargados = 0;
            foreach ($diag as $f) {
                $bienId = $this->cargarFila($f, $usuario, $bienesPorGrupo);
                $cargados += $f['carga'] ? 1 : 0;
                $this->registrarBitacora($lote, $f, $bienId);
            }

            // Reconciliación: lo realmente cargado debe coincidir con la fórmula de la tesis.
            if ($cargados !== $lote->cargados || ! $lote->cuadra()) {
                throw ReglaNegocioException::en('archivo', 'La reconciliación no cuadra; la importación se revirtió.');
            }

            Auditor::registrar('importar_depurados', $lote, $resumen);

            return $lote;
        });
    }

    /** Crea el bien si la fila se carga; para fusionadas secundarias devuelve el bien del grupo. */
    private function cargarFila(array $f, User $usuario, array &$bienesPorGrupo): ?int
    {
        if (! $f['carga']) {
            return mb_strtolower($f['resultado']) === 'fusionada' ? ($bienesPorGrupo[$f['grupo_fusion']] ?? null) : null;
        }

        $situacion = mb_strtolower($f['resultado']) === 'pendiente' ? Situacion::NO_UBICADO : Situacion::ACTIVO;
        $bien = $this->registroBien->registrar([
            'codigo_patrimonial' => $f['codigo_patrimonial'],
            'codigo_catalogo_siga' => $f['codigo_catalogo_siga'],
            'descripcion' => $f['descripcion'],
            'marca' => $f['marca'],
            'modelo' => $f['modelo'],
            'serie' => $f['serie'],
            'ambiente_id' => $f['ambiente_id'],
            'responsable_id' => $f['responsable_id'],
            'estado_id' => $f['estado_id'],
            'fecha_ultima_verificacion' => $f['fecha_verificacion'],
        ], $usuario, $f['id_origen'], $situacion);

        if ($f['principal']) {
            $bienesPorGrupo[$f['grupo_fusion']] = $bien->id;
        }

        return $bien->id;
    }

    private function registrarBitacora(LoteImportacion $lote, array $f, ?int $bienId): void
    {
        BitacoraMigracion::create([
            'lote_id' => $lote->id,
            'id_origen' => $f['id_origen'],
            'reglas' => implode(';', $f['marcas']) ?: null,
            'dato_anterior' => $f['dato_anterior'],
            'dato_nuevo' => $f['dato_nuevo'],
            'evidencia' => $f['evidencia'],
            'autorizado_por' => $f['autorizado_por'],
            'fecha' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $f['fecha_autorizacion']) ? $f['fecha_autorizacion'] : null,
            'resultado' => mb_strtolower($f['resultado']),
            'grupo_fusion' => $f['grupo_fusion'],
            'bien_id' => $bienId,
            'creado_en' => now(),
        ]);
    }
}
