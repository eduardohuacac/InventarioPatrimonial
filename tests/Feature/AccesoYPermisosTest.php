<?php

namespace Tests\Feature;

use App\Enums\TipoMovimiento;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenario;
use Tests\TestCase;

/** RF01, RF12, RF13 y CP06: autenticación, permisos por perfil y auditoría. */
class AccesoYPermisosTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararCatalogos();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/bienes')->assertRedirect(route('login'));
    }

    public function test_login_correcto_e_incorrecto_quedan_en_auditoria(): void
    {
        $u = User::factory()->create(['email' => 'enc@ie.pe', 'password' => 'Clave1234']);

        $this->post('/login', ['email' => 'enc@ie.pe', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('auditorias', ['accion' => 'acceso_fallido']);

        $this->post('/login', ['email' => 'enc@ie.pe', 'password' => 'Clave1234'])->assertRedirect(route('panel'));
        $this->assertAuthenticatedAs($u);
        $this->assertDatabaseHas('auditorias', ['accion' => 'iniciar_sesion', 'usuario_id' => $u->id]);
    }

    public function test_login_se_bloquea_tras_cinco_intentos(): void
    {
        User::factory()->create(['email' => 'x@ie.pe', 'password' => 'Clave1234']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'x@ie.pe', 'password' => 'mala']);
        }
        $this->post('/login', ['email' => 'x@ie.pe', 'password' => 'Clave1234'])->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_usuario_inactivo_no_puede_ingresar(): void
    {
        User::factory()->create(['email' => 'off@ie.pe', 'password' => 'Clave1234', 'activo' => false]);
        $this->post('/login', ['email' => 'off@ie.pe', 'password' => 'Clave1234'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** CP06: perfil Consulta intenta ver bienes de otro → denegado. */
    public function test_cp06_consulta_no_ve_bienes_de_otro(): void
    {
        $docente = Personal::factory()->create();
        $otro = Personal::factory()->create();
        $propio = $this->crearBien(['responsable_id' => $docente->id]);
        $ajeno = $this->crearBien(['responsable_id' => $otro->id]);
        $usuario = User::factory()->perfil('CONSULTA')->create(['personal_id' => $docente->id]);

        $this->actingAs($usuario)->get(route('bienes.show', $propio))->assertOk();
        $this->actingAs($usuario)->get(route('bienes.show', $ajeno))->assertForbidden();
        $this->actingAs($usuario)->get(route('bienes.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('mis-bienes'))->assertOk()
            ->assertSee($propio->codigo_patrimonial)->assertDontSee($ajeno->codigo_patrimonial);
    }

    public function test_administrador_no_registra_movimientos_ni_bienes(): void
    {
        $admin = User::factory()->perfil('ADMIN')->create();
        $bien = $this->crearBien();

        $this->actingAs($admin)->get(route('bienes.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('movimientos.create', [$bien, 'traslado']))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.usuarios.index'))->assertOk();
    }

    public function test_director_no_registra_pero_aprueba(): void
    {
        $bien = $this->crearBien();
        $this->actingAs($this->director)->get(route('bienes.show', $bien))->assertOk();
        $this->actingAs($this->director)->get(route('movimientos.create', [$bien, 'traslado']))->assertForbidden();
        $this->actingAs($this->director)->get(route('aprobaciones.index'))->assertOk();
    }

    public function test_encargado_no_puede_aprobar(): void
    {
        $bien = $this->crearBien();
        $sol = $this->movimiento($bien, TipoMovimiento::SOLICITUD_BAJA, ['motivo' => 'Daño irreparable del bien.']);
        $this->actingAs($this->encargado)->post(route('aprobaciones.decidir', $sol), ['decision' => 'APROBADO'])->assertForbidden();
    }

    public function test_cabeceras_de_seguridad(): void
    {
        $this->get('/login')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_crear_usuario_consulta_exige_persona_vinculada(): void
    {
        $admin = User::factory()->perfil('ADMIN')->create();
        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'Docente', 'email' => 'doc@ie.pe', 'perfil' => 'CONSULTA',
            'password' => 'Clave1234', 'password_confirmation' => 'Clave1234', 'activo' => 1,
        ])->assertSessionHasErrors('personal_id');
    }

    public function test_contrasena_debil_es_rechazada(): void
    {
        $admin = User::factory()->perfil('ADMIN')->create();
        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'X', 'email' => 'x2@ie.pe', 'perfil' => 'ENCARGADO',
            'password' => '1234', 'password_confirmation' => '1234', 'activo' => 1,
        ])->assertSessionHasErrors('password');
    }
}
