<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos administrados por el perfil Administrador:
 * personal (responsables), ambientes y estados de conservación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 150);
            $table->string('cargo', 100);
            // Ley N.° 29733: el DNI es opcional y se muestra enmascarado.
            $table->string('dni', 8)->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('ambientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->string('tipo', 30); // aula, oficina, laboratorio, almacen, otro
            $table->string('piso', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('estados_conservacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20)->unique(); // bueno, regular, malo
            $table->unsignedTinyInteger('orden')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('personal_id')->nullable()->after('perfil')
                ->constrained('personal')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_id');
        });
        Schema::dropIfExists('estados_conservacion');
        Schema::dropIfExists('ambientes');
        Schema::dropIfExists('personal');
    }
};
