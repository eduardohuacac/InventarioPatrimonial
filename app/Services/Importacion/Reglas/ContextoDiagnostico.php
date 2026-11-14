<?php

namespace App\Services\Importacion\Reglas;

use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\EstadoConservacion;
use App\Models\Personal;
use Illuminate\Support\Collection;

/**
 * Datos precalculados que comparten las reglas de calidad: catálogos del
 * sistema e índices del archivo (por código y por firma descriptiva).
 */
class ContextoDiagnostico
{
    /** @var array<string, int[]> */
    public array $porCodigo = [];

    /** @var array<string, int[]> */
    public array $porFirma = [];

    private Collection $ambientes;

    private Collection $estados;

    private Collection $personal;

    private Collection $codigosEnBd;

    public function __construct(public readonly array $filas)
    {
        $this->ambientes = Ambiente::where('activo', true)->pluck('id', 'nombre')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id]);
        $this->estados = EstadoConservacion::pluck('id', 'nombre')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id]);
        $this->personal = Personal::where('activo', true)->get(['id', 'nombres', 'dni']);
        $this->codigosEnBd = Bien::pluck('codigo_patrimonial')->map(fn ($c) => mb_strtoupper($c))->flip();

        foreach ($filas as $i => $f) {
            if ($codigo = self::codigo($f)) {
                $this->porCodigo[$codigo][] = $i;
            }
            if ($firma = self::firma($f)) {
                $this->porFirma[$firma][] = $i;
            }
        }
    }

    public static function codigo(array $f): ?string
    {
        return $f['codigo_patrimonial'] ? mb_strtoupper($f['codigo_patrimonial']) : null;
    }

    /** Descripción + marca + modelo + serie (solo si hay serie). */
    public static function firma(array $f): ?string
    {
        return ($f['serie'] && $f['descripcion'])
            ? mb_strtolower($f['descripcion'].'|'.$f['marca'].'|'.$f['modelo'].'|'.$f['serie'])
            : null;
    }

    public function existeEnBd(string $codigo): bool
    {
        return isset($this->codigosEnBd[$codigo]);
    }

    public function ambienteId(?string $nombre): ?int
    {
        return $nombre ? ($this->ambientes[mb_strtolower($nombre)] ?? null) : null;
    }

    public function estadoId(?string $nombre): ?int
    {
        return $nombre ? ($this->estados[mb_strtolower($nombre)] ?? null) : null;
    }

    /** Busca por nombre completo exacto o por DNI. */
    public function responsableId(?string $texto): ?int
    {
        if (! $texto) {
            return null;
        }
        $texto = mb_strtolower(trim($texto));

        return $this->personal->first(fn ($p) => mb_strtolower($p->nombres) === $texto || $p->dni === $texto)?->id;
    }
}
