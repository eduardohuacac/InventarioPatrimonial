<?php

/**
 * Herramienta propia de métricas de código (AG-C113).
 *
 * Calcula, sin dependencias externas, por cada método de las clases en app/:
 *   - Complejidad ciclomática de McCabe: 1 + número de puntos de decisión
 *     (if, elseif, for, foreach, while, case, catch, &&, ||, ?:, ??, match arm)
 *   - Líneas de código (LOC)
 * y genera un resumen con umbrales (CC ≤ 10 aceptable, 11–20 moderada, > 20 alta).
 *
 * Uso:  php tools/metricas.php [--json] [--umbral=10]
 * Sale con código 1 si algún método supera 20 (útil en integración continua).
 */

$base = dirname(__DIR__).'/app';
$json = in_array('--json', $argv, true);
$umbral = 10;
foreach ($argv as $a) {
    if (str_starts_with($a, '--umbral=')) {
        $umbral = (int) substr($a, 9);
    }
}

$decision = [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_CASE, T_CATCH, T_BOOLEAN_AND, T_BOOLEAN_OR, T_LOGICAL_AND, T_LOGICAL_OR, T_COALESCE];

$metodos = [];
$archivos = 0;
$clases = 0;
$locTotal = 0;

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') {
        continue;
    }
    $archivos++;
    $codigo = file_get_contents($f->getPathname());
    $locTotal += count(array_filter(explode("\n", $codigo), fn ($l) => trim($l) !== '' && ! preg_match('#^\s*(//|\*|/\*)#', $l)));
    $tokens = token_get_all($codigo);
    $clase = null;
    $n = count($tokens);

    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        if (is_array($t) && in_array($t[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            for ($j = $i + 1; $j < $n; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $clase = $tokens[$j][1];
                    $clases++;
                    break;
                }
                if ($tokens[$j] === '{' || $tokens[$j] === '(') {
                    break;
                }
            }
        }
        if (! (is_array($t) && $t[0] === T_FUNCTION)) {
            continue;
        }
        // Nombre del método (las funciones anónimas no tienen nombre y se cuentan en su método contenedor).
        $nombre = null;
        for ($j = $i + 1; $j < $n; $j++) {
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $nombre = $tokens[$j][1];
                break;
            }
            if ($tokens[$j] === '(') {
                break;
            }
        }
        if (! $nombre) {
            continue;
        }
        // Buscar el cuerpo { ... }; los métodos abstractos o de interfaz terminan en ';'.
        $j = $i;
        while ($j < $n && $tokens[$j] !== '{' && $tokens[$j] !== ';') {
            $j++;
        }
        if ($j >= $n || $tokens[$j] === ';') {
            continue;
        }
        $nivel = 0;
        $cc = 1;
        $flechasDeFn = 0; // el => de una función flecha no es un brazo de match
        $lineaInicio = $t[2];
        $lineaFin = $lineaInicio;
        for ($k = $j; $k < $n; $k++) {
            $tk = $tokens[$k];
            if ($tk === '{' || (is_array($tk) && in_array($tk[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $nivel++;
            } elseif ($tk === '}') {
                $nivel--;
                if ($nivel === 0) {
                    break;
                }
            } elseif ($tk === '?') {
                $cc++; // operador ternario
            } elseif (is_array($tk)) {
                $lineaFin = $tk[2];
                if (in_array($tk[0], $decision, true)) {
                    $cc++;
                } elseif ($tk[0] === T_FN) {
                    $flechasDeFn++;
                } elseif ($tk[0] === T_DOUBLE_ARROW && $flechasDeFn > 0 && es_flecha_de_fn($tokens, $k)) {
                    $flechasDeFn--;
                } elseif ($tk[0] === T_DOUBLE_ARROW && self_en_match($tokens, $k)) {
                    $cc++;
                }
            }
        }
        $metodos[] = [
            'archivo' => str_replace(dirname(__DIR__).'/', '', $f->getPathname()),
            'metodo' => ($clase ?? '?').'::'.$nombre,
            'cc' => $cc,
            'loc' => $lineaFin - $lineaInicio + 1,
        ];
        $i = $k;
    }
}

/** ¿El => cierra la firma de una función flecha "fn (...) =>"? (el token previo significativo es ')' o un tipo de retorno) */
function es_flecha_de_fn(array $tokens, int $pos): bool
{
    for ($k = $pos - 1; $k >= 0; $k--) {
        $t = $tokens[$k];
        if (is_array($t) && $t[0] === T_WHITESPACE) {
            continue;
        }
        if ($t === ')') {
            return true;
        }
        if (is_array($t) && in_array($t[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_ARRAY, T_STATIC], true)) {
            continue; // tipo de retorno: fn (): int =>
        }

        return in_array($t, [':', '?', '|'], true);
    }

    return false;
}

/** ¿El => pertenece a un brazo de match (y no a un arreglo)? Aproximación: hay un "match" abierto antes en la misma expresión. */
function self_en_match(array $tokens, int $pos): bool
{
    $profundidad = 0;
    for ($k = $pos - 1; $k >= 0 && $k > $pos - 400; $k--) {
        $t = $tokens[$k];
        if ($t === '}' ) {
            $profundidad++;
        } elseif ($t === '{') {
            if ($profundidad === 0) {
                // Revisa si este { es el de un match (...)
                for ($m = $k - 1; $m >= 0 && $m > $k - 60; $m--) {
                    if (is_array($tokens[$m]) && $tokens[$m][0] === T_MATCH) {
                        return true;
                    }
                    if ($tokens[$m] === ';' || $tokens[$m] === '{' || $tokens[$m] === '}') {
                        return false;
                    }
                }

                return false;
            }
            $profundidad--;
        } elseif ($t === '[' && $profundidad === 0) {
            return false;
        }
    }

    return false;
}

usort($metodos, fn ($a, $b) => $b['cc'] <=> $a['cc']);
$total = count($metodos);
$ccs = array_column($metodos, 'cc');
$resumen = [
    'archivos' => $archivos,
    'clases' => $clases,
    'metodos' => $total,
    'loc' => $locTotal,
    'cc_promedio' => $total ? round(array_sum($ccs) / $total, 2) : 0,
    'cc_maxima' => $total ? max($ccs) : 0,
    'metodos_cc_hasta_10' => count(array_filter($ccs, fn ($c) => $c <= 10)),
    'metodos_cc_11_a_20' => count(array_filter($ccs, fn ($c) => $c > 10 && $c <= 20)),
    'metodos_cc_mayor_20' => count(array_filter($ccs, fn ($c) => $c > 20)),
    'porcentaje_aceptable' => $total ? round(count(array_filter($ccs, fn ($c) => $c <= $umbral)) * 100 / $total, 1) : 100,
];

if ($json) {
    echo json_encode(['resumen' => $resumen, 'metodos' => $metodos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
} else {
    echo "== Métricas de código (app/) ==\n";
    foreach ($resumen as $k => $v) {
        printf("  %-24s %s\n", $k, $v);
    }
    echo "\n== 10 métodos con mayor complejidad ciclomática ==\n";
    foreach (array_slice($metodos, 0, 10) as $m) {
        printf("  CC %2d  LOC %3d  %s\n", $m['cc'], $m['loc'], $m['metodo']);
    }
}

exit($resumen['metodos_cc_mayor_20'] > 0 ? 1 : 0);
