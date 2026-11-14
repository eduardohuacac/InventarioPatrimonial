<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad: lotes de importación de registros depurados, bitácora de
 * migración (OE2) y auditoría de operaciones (RF13).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_importacion', function (Blueprint $table) {
            $table->id();
            $table->string('archivo_nombre');
            $table->string('archivo_hash', 64);
            $table->unsignedInteger('filas_origen');
            $table->unsignedInteger('conservadas')->default(0);
            $table->unsignedInteger('corregidas')->default(0);
            $table->unsignedInteger('fusionadas')->default(0);
            $table->unsignedInteger('grupos_fusion')->default(0);
            $table->unsignedInteger('pendientes')->default(0);
            $table->unsignedInteger('excluidas')->default(0);
            $table->unsignedInteger('cargados')->default(0);
            $table->foreignId('importado_por')->constrained('users');
            $table->timestamp('importado_en')->useCurrent();
        });

        Schema::create('bitacora_migracion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes_importacion');
            $table->string('id_origen', 30)->index();
            $table->string('reglas', 30)->nullable(); // R1;R3...
            $table->text('dato_anterior')->nullable();
            $table->text('dato_nuevo')->nullable();
            $table->string('evidencia', 255)->nullable();
            $table->string('autorizado_por', 150)->nullable();
            $table->date('fecha')->nullable();
            $table->string('resultado', 12); // conservada|corregida|fusionada|pendiente|excluida
            $table->string('grupo_fusion', 30)->nullable();
            $table->foreignId('bien_id')->nullable()->constrained('bienes');
            $table->timestamp('creado_en')->useCurrent();
        });

        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->string('accion', 40)->index();
            $table->string('entidad', 40)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->json('detalle')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('fecha_hora')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
        Schema::dropIfExists('bitacora_migracion');
        Schema::dropIfExists('lotes_importacion');
    }
};
