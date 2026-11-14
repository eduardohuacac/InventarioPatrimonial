<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Núcleo del sistema: bienes, documentos de sustento, movimientos (historial
 * de solo inserción) y aprobaciones de la Dirección (también de solo inserción).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bienes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_patrimonial', 20)->unique();
            $table->string('codigo_catalogo_siga', 20)->nullable()->index();
            $table->string('descripcion', 255);
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 80)->nullable();
            $table->string('serie', 80)->nullable();
            $table->date('fecha_ingreso')->nullable();
            // Situación vigente: se recalcula a partir del historial de movimientos.
            $table->foreignId('ambiente_actual_id')->constrained('ambientes');
            $table->foreignId('responsable_actual_id')->constrained('personal');
            $table->foreignId('estado_actual_id')->constrained('estados_conservacion');
            $table->string('situacion', 20)->default('activo')->index();
            $table->date('fecha_ultima_verificacion');
            $table->date('fecha_prevista_devolucion')->nullable(); // préstamo vigente
            $table->string('id_origen', 30)->nullable()->index(); // p. ej. H1-023
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();

            $table->index('descripcion');
        });

        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40);
            $table->string('numero', 40);
            $table->date('fecha');
            $table->string('archivo_path')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();
            $table->unique(['tipo', 'numero']);
        });

        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bien_id')->constrained('bienes');
            $table->string('tipo', 20)->index();
            $table->date('fecha');
            $table->foreignId('ambiente_origen_id')->nullable()->constrained('ambientes');
            $table->foreignId('ambiente_destino_id')->nullable()->constrained('ambientes');
            $table->foreignId('responsable_anterior_id')->nullable()->constrained('personal');
            $table->foreignId('responsable_nuevo_id')->nullable()->constrained('personal');
            $table->foreignId('estado_id')->nullable()->constrained('estados_conservacion');
            $table->foreignId('documento_id')->nullable()->constrained('documentos');
            $table->date('fecha_prevista_devolucion')->nullable();
            $table->boolean('encontrado')->nullable(); // solo en VERIFICACION
            $table->foreignId('movimiento_anulado_id')->nullable()->constrained('movimientos');
            $table->text('motivo')->nullable();
            $table->string('observacion', 255)->nullable();
            $table->boolean('requiere_aprobacion')->default(false);
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamp('registrado_en')->useCurrent();

            $table->index(['bien_id', 'fecha']);
        });

        // Decisión de la Dirección sobre un movimiento que requiere aprobación.
        // Se guarda aparte para que la tabla movimientos nunca reciba UPDATE.
        Schema::create('aprobaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->unique()->constrained('movimientos');
            $table->string('decision', 10); // APROBADO | RECHAZADO
            $table->string('observacion', 255)->nullable();
            $table->foreignId('decidido_por')->constrained('users');
            $table->timestamp('decidido_en')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aprobaciones');
        Schema::dropIfExists('movimientos');
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('bienes');
    }
};
