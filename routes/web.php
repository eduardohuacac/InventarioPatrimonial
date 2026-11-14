<?php

use App\Http\Controllers\Admin\AmbienteController;
use App\Http\Controllers\Admin\PersonalController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AprobacionController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CorreccionController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\MisBienesController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

// Autenticación (RF01)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'ingresar'])->name('login.ingresar');
});
Route::post('/logout', [LoginController::class, 'salir'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', PanelController::class)->name('panel');
    Route::get('/mis-bienes', MisBienesController::class)->name('mis-bienes');
    Route::get('/perfil/clave', [PerfilController::class, 'editar'])->name('perfil.clave');
    Route::put('/perfil/clave', [PerfilController::class, 'actualizar'])->name('perfil.clave.actualizar');

    // Ficha del bien: la política decide si el perfil Consulta puede verla (CP06).
    Route::get('/bienes/{bien}', [BienController::class, 'show'])->name('bienes.show');
    Route::get('/documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])->name('documentos.archivo');

    Route::middleware('perfil:DIRECTOR,ENCARGADO')->group(function () {
        Route::get('/bienes', [BienController::class, 'index'])->name('bienes.index');
        Route::get('/aprobaciones', [AprobacionController::class, 'index'])->name('aprobaciones.index');
        Route::get('/alertas', AlertaController::class)->name('alertas');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');
        Route::get('/importacion', [ImportacionController::class, 'index'])->name('importacion.index');
        Route::get('/importacion/lotes/{lote}', [ImportacionController::class, 'show'])->name('importacion.show');
    });

    Route::middleware('perfil:ENCARGADO')->group(function () {
        Route::get('/bienes-nuevo', [BienController::class, 'create'])->name('bienes.create');
        Route::post('/bienes', [BienController::class, 'store'])->name('bienes.store');
        Route::get('/bienes/{bien}/editar', [BienController::class, 'edit'])->name('bienes.edit');
        Route::put('/bienes/{bien}', [BienController::class, 'update'])->name('bienes.update');

        Route::get('/bienes/{bien}/movimientos/{tipo}', [MovimientoController::class, 'create'])->name('movimientos.create');
        Route::post('/bienes/{bien}/movimientos/{tipo}', [MovimientoController::class, 'store'])->name('movimientos.store');
        Route::get('/movimientos/{movimiento}/corregir', [CorreccionController::class, 'create'])->name('correcciones.create');
        Route::post('/movimientos/{movimiento}/corregir', [CorreccionController::class, 'store'])->name('correcciones.store');

        Route::get('/importacion/plantilla', [ImportacionController::class, 'plantilla'])->name('importacion.plantilla');
        Route::post('/importacion/previsualizar', [ImportacionController::class, 'previsualizar'])->name('importacion.previsualizar');
        Route::post('/importacion/confirmar', [ImportacionController::class, 'confirmar'])->name('importacion.confirmar');
    });

    Route::post('/aprobaciones/{movimiento}', [AprobacionController::class, 'decidir'])
        ->middleware('perfil:DIRECTOR')->name('aprobaciones.decidir');

    Route::get('/auditoria', AuditoriaController::class)->middleware('perfil:ADMIN,DIRECTOR')->name('auditoria');

    // Administración (RF02)
    Route::middleware('perfil:ADMIN')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy'])
            ->parameters(['usuarios' => 'usuario']);
        Route::resource('ambientes', AmbienteController::class)->except(['show', 'destroy']);
        Route::resource('personal', PersonalController::class)->except(['show', 'destroy'])
            ->parameters(['personal' => 'personal']);
    });
});
