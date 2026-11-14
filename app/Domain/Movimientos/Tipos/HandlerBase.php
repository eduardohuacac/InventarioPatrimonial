<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Domain\Movimientos\TipoMovimientoHandler;
use App\Exceptions\ReglaNegocioException;
use Carbon\CarbonImmutable;

abstract class HandlerBase implements TipoMovimientoHandler
{
    public function documentoObligatorio(): bool
    {
        return true;
    }

    public function requiereAprobacion(): bool
    {
        return false;
    }

    public function reglas(SituacionBien $actual): array
    {
        return [];
    }

    public function validarContexto(SituacionBien $actual, array $datos): void {}

    public function seAplica(string $estadoAprobacion): bool
    {
        return in_array($estadoAprobacion, ['NO_REQUIERE', 'APROBADO'], true);
    }

    protected function fecha(mixed $valor): ?CarbonImmutable
    {
        return $valor ? CarbonImmutable::parse($valor)->startOfDay() : null;
    }

    protected function exigir(bool $condicion, string $campo, string $mensaje): void
    {
        if (! $condicion) {
            throw ReglaNegocioException::en($campo, $mensaje);
        }
    }
}
