<?php

namespace Database\Seeders;

use App\Domain\Movimientos\FabricaHandlers;
use App\Enums\Decision;
use App\Enums\TipoMovimiento;
use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\EstadoConservacion;
use App\Models\Personal;
use App\Models\User;
use App\Services\RegistroBienService;
use App\Services\RegistroMovimientoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración FICTICIOS (Ley N.° 29733: no se usan datos reales del
 * personal). Todo se registra mediante los servicios del sistema, de modo que
 * el historial y la situación vigente son coherentes.
 */
class DemoSeeder extends Seeder
{
    private int $doc = 1;

    public function run(): void
    {
        mt_srand(50023);
        $estados = EstadoConservacion::pluck('id', 'nombre');

        $ambientes = collect([
            ['Dirección', 'oficina', '1'], ['Secretaría', 'oficina', '1'], ['Sala de profesores', 'oficina', '1'],
            ['Aula 1.° A', 'aula', '1'], ['Aula 2.° A', 'aula', '1'], ['Aula 3.° A', 'aula', '2'], ['Aula 4.° A', 'aula', '2'],
            ['Aula 5.° A', 'aula', '2'], ['Aula 6.° A', 'aula', '2'], ['Laboratorio de Ciencias', 'laboratorio', '2'],
            ['Aula de Innovación Pedagógica', 'laboratorio', '2'], ['Biblioteca', 'biblioteca', '1'], ['Almacén', 'almacen', '1'],
        ])->map(fn ($a) => Ambiente::create(['nombre' => $a[0], 'tipo' => $a[1], 'piso' => $a[2], 'activo' => true]));

        $personal = collect([
            ['Ana María Condori Huamán', 'Directora'], ['Julio César Mamani Quispe', 'Encargado de patrimonio'],
            ['Rosa Elena Quispe Mamani', 'Docente 1.° grado'], ['Luis Alberto Huamán Ccori', 'Docente 2.° grado'],
            ['Carmen Rosa Ttito Apaza', 'Docente 3.° grado'], ['Pedro Pablo Choque Layme', 'Docente 4.° grado'],
            ['Marisol Paucar Yupanqui', 'Docente 5.° grado'], ['Wilber Sutta Cusi', 'Docente 6.° grado'],
            ['Elizabeth Ccahuana Rojas', 'Docente de Ciencia y Tecnología'], ['Hernán Puma Villca', 'Docente de Educación para el Trabajo'],
            ['Gloria Inca Roca Alanoca', 'Secretaria'], ['Fredy Huallpa Tupa', 'Auxiliar de biblioteca'],
        ])->map(fn ($p) => Personal::create(['nombres' => $p[0], 'cargo' => $p[1], 'activo' => true]));

        $director = User::create(['name' => 'Ana María Condori (Directora)', 'email' => 'director@ie50023.edu.pe', 'password' => 'Patrimonio2026', 'perfil' => 'DIRECTOR', 'personal_id' => $personal[0]->id, 'activo' => true]);
        $encargado = User::create(['name' => 'Julio César Mamani (Encargado)', 'email' => 'patrimonio@ie50023.edu.pe', 'password' => 'Patrimonio2026', 'perfil' => 'ENCARGADO', 'personal_id' => $personal[1]->id, 'activo' => true]);
        User::create(['name' => 'Rosa Elena Quispe', 'email' => 'rquispe@ie50023.edu.pe', 'password' => 'Patrimonio2026', 'perfil' => 'CONSULTA', 'personal_id' => $personal[2]->id, 'activo' => true]);
        User::create(['name' => 'Luis Alberto Huamán', 'email' => 'lhuaman@ie50023.edu.pe', 'password' => 'Patrimonio2026', 'perfil' => 'CONSULTA', 'personal_id' => $personal[3]->id, 'activo' => true]);

        $catalogo = [
            ['Mesa de madera para docente', null, null, '74088037', 'Sala de profesores', 2],
            ['Silla de madera para docente', null, null, '74089950', 'Sala de profesores', 2],
            ['Mesa unipersonal para estudiante', null, null, '74088150', null, 3],
            ['Silla unipersonal para estudiante', null, null, '74089980', null, 3],
            ['Pizarra acrílica 1.20 x 2.40 m', null, null, '74087700', null, 1],
            ['Computadora de escritorio', 'Lenovo', 'ThinkCentre M70s', '74089500', 'Aula de Innovación Pedagógica', 6],
            ['Laptop educativa', 'Lenovo', '100e', '74089520', 'Aula de Innovación Pedagógica', 6],
            ['Proyector multimedia', 'Epson', 'PowerLite E20', '74224300', 'Aula de Innovación Pedagógica', 2],
            ['Impresora multifuncional', 'Epson', 'EcoTank L5590', '74086200', 'Secretaría', 1],
            ['Archivador de metal de 4 gavetas', null, null, '74080500', 'Dirección', 2],
            ['Microscopio binocular', 'Olympus', 'CX23', '53648600', 'Laboratorio de Ciencias', 3],
            ['Estante de madera', null, null, '74082300', 'Biblioteca', 3],
            ['Equipo de sonido', 'Sony', 'MHC-V43D', '74225900', 'Dirección', 1],
            ['Televisor LED 50"', 'Samsung', 'UN50', '74226000', 'Sala de profesores', 1],
        ];

        $responsablePorAmbiente = [
            'Dirección' => 0, 'Secretaría' => 10, 'Sala de profesores' => 1, 'Aula 1.° A' => 2, 'Aula 2.° A' => 3,
            'Aula 3.° A' => 4, 'Aula 4.° A' => 5, 'Aula 5.° A' => 6, 'Aula 6.° A' => 7, 'Laboratorio de Ciencias' => 8,
            'Aula de Innovación Pedagógica' => 9, 'Biblioteca' => 11, 'Almacén' => 1,
        ];

        $registro = app(RegistroBienService::class);
        $mov = app(RegistroMovimientoService::class);
        $n = 1;
        $bienes = collect();
        $aulas = $ambientes->filter(fn ($a) => $a->tipo === 'aula')->values();

        foreach ($catalogo as [$desc, $marca, $modelo, $siga, $ambNombre, $cantidad]) {
            for ($i = 0; $i < $cantidad; $i++) {
                $amb = $ambNombre ? $ambientes->firstWhere('nombre', $ambNombre) : $aulas[$i % $aulas->count()];
                $resp = $personal[$responsablePorAmbiente[$amb->nombre]];
                $estado = ['bueno', 'bueno', 'bueno', 'regular', 'regular', 'malo'][mt_rand(0, 5)];
                $verif = Carbon::create(2025, mt_rand(3, 12), mt_rand(1, 28));
                $bienes->push($registro->registrar([
                    'codigo_patrimonial' => $siga.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'codigo_catalogo_siga' => $siga,
                    'descripcion' => $desc,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'serie' => $marca ? strtoupper(substr($marca, 0, 2)).mt_rand(100000, 999999) : null,
                    'fecha_ingreso' => $verif->copy()->subYears(mt_rand(1, 6))->toDateString(),
                    'ambiente_id' => $amb->id,
                    'responsable_id' => $resp->id,
                    'estado_id' => $estados[$estado],
                    'fecha_ultima_verificacion' => $verif->toDateString(),
                    'documento_tipo' => 'comprobante_ingreso',
                    'documento_numero' => 'PECOSA-'.str_pad((string) mt_rand(1, 120), 4, '0', STR_PAD_LEFT),
                ], $encargado));
                $n++;
            }
        }

        $hoy = today();
        $d = fn (int $dias) => $hoy->copy()->subDays($dias)->toDateString();
        $laptops = $bienes->filter(fn (Bien $b) => str_starts_with($b->descripcion, 'Laptop'))->values();
        $proyectores = $bienes->filter(fn (Bien $b) => str_starts_with($b->descripcion, 'Proyector'))->values();

        // Verificaciones recientes (parte del inventario 2026 ya avanzado).
        // Fechas anteriores a los demás movimientos de la demo (que van de hace 50 días a hoy).
        foreach ($bienes->filter(fn ($b, $i) => $i % 2 === 0)->values() as $b) {
            $this->mov($mov, $b, TipoMovimiento::VERIFICACION, $encargado, $d(mt_rand(60, 150)), ['encontrado' => 1, 'estado_id' => $b->estado_actual_id]);
        }

        // Préstamos: uno vigente, dos vencidos y uno devuelto.
        $this->mov($mov, $laptops[0], TipoMovimiento::PRESTAMO, $encargado, $d(3), ['responsable_nuevo_id' => $personal[4]->id, 'fecha_prevista_devolucion' => $hoy->copy()->addDays(4)->toDateString(), 'ambiente_destino_id' => $ambientes->firstWhere('nombre', 'Aula 3.° A')->id]);
        $this->mov($mov, $laptops[1], TipoMovimiento::PRESTAMO, $encargado, $d(25), ['responsable_nuevo_id' => $personal[5]->id, 'fecha_prevista_devolucion' => $d(11), 'ambiente_destino_id' => $ambientes->firstWhere('nombre', 'Aula 4.° A')->id]);
        $this->mov($mov, $proyectores[0], TipoMovimiento::PRESTAMO, $encargado, $d(15), ['responsable_nuevo_id' => $personal[2]->id, 'fecha_prevista_devolucion' => $d(8)]);
        $this->mov($mov, $laptops[2], TipoMovimiento::PRESTAMO, $encargado, $d(40), ['responsable_nuevo_id' => $personal[6]->id, 'fecha_prevista_devolucion' => $d(33)]);
        $this->mov($mov, $laptops[2], TipoMovimiento::TRASLADO, $encargado, $d(36), ['ambiente_destino_id' => $ambientes->firstWhere('nombre', 'Aula 5.° A')->id]);
        $this->mov($mov, $laptops[2], TipoMovimiento::DEVOLUCION, $encargado, $d(32), ['responsable_nuevo_id' => $personal[9]->id, 'estado_id' => $estados['regular'], 'ambiente_destino_id' => $ambientes->firstWhere('nombre', 'Aula de Innovación Pedagógica')->id]);

        // Traslados, asignaciones y cambios de estado.
        $sillas = $bienes->filter(fn (Bien $b) => str_starts_with($b->descripcion, 'Silla unipersonal'))->values();
        $this->mov($mov, $sillas[0], TipoMovimiento::TRASLADO, $encargado, $d(50), ['ambiente_destino_id' => $ambientes->firstWhere('nombre', 'Biblioteca')->id]);
        $this->mov($mov, $sillas[0], TipoMovimiento::ASIGNACION, $encargado, $d(49), ['ambiente_destino_id' => $ambientes->firstWhere('nombre', 'Biblioteca')->id, 'responsable_nuevo_id' => $personal[11]->id]);
        $micro = $bienes->first(fn (Bien $b) => str_starts_with($b->descripcion, 'Microscopio'));
        $this->mov($mov, $micro, TipoMovimiento::CAMBIO_ESTADO, $encargado, $d(30), ['estado_id' => $micro->estado_actual_id == $estados['malo'] ? $estados['regular'] : $estados['malo']]);

        // Bien no ubicado en la verificación.
        $this->mov($mov, $bienes->first(fn (Bien $b) => str_starts_with($b->descripcion, 'Equipo de sonido')), TipoMovimiento::VERIFICACION, $encargado, $d(18), ['encontrado' => 0]);

        // Solicitudes de baja: una aprobada y otra pendiente.
        $mesas = $bienes->filter(fn (Bien $b) => str_starts_with($b->descripcion, 'Mesa unipersonal'))->values();
        $s1 = $this->mov($mov, $mesas[0], TipoMovimiento::SOLICITUD_BAJA, $encargado, $d(45), ['motivo' => 'Estructura rota y madera apolillada; reparación no viable según informe técnico.']);
        $mov->decidir($s1, Decision::APROBADO, 'Elevar a la UGEL Cusco.', $director);
        $this->mov($mov, $mesas[1], TipoMovimiento::SOLICITUD_BAJA, $encargado, $d(6), ['motivo' => 'Tablero partido por uso; no es seguro para los estudiantes.']);

        // Corrección pendiente de aprobación (traslado registrado en el bien equivocado).
        $pizarra = $bienes->first(fn (Bien $b) => str_starts_with($b->descripcion, 'Pizarra'));
        $erroneo = $this->mov($mov, $pizarra, TipoMovimiento::TRASLADO, $encargado, $d(4), ['ambiente_destino_id' => $ambientes->firstWhere('nombre', 'Almacén')->id]);
        $mov->registrarCorreccion($erroneo, ['fecha' => $d(2), 'motivo' => 'El traslado correspondía a otra pizarra; esta sigue en el aula.'], $encargado);
    }

    private function mov(RegistroMovimientoService $s, Bien $bien, TipoMovimiento $tipo, User $u, string $fecha, array $datos)
    {
        $docTipo = app(FabricaHandlers::class)->para($tipo)->documentosAceptados()[0]->value;

        return $s->registrar($bien->fresh(), $tipo, array_merge([
            'fecha' => $fecha,
            'documento_tipo' => $docTipo,
            'documento_numero' => str_pad((string) $this->doc++, 3, '0', STR_PAD_LEFT).'-2026',
            'documento_fecha' => $fecha,
        ], $datos), $u);
    }
}
