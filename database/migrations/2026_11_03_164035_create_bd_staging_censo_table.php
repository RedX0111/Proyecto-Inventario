<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bd_staging_censo', function (Blueprint $table) {
            $table->bigIncrements('stg_id');
            $table->integer('stg_orden')->nullable();
            $table->string('codigo_patrimonial', 50)->nullable();
            $table->string('denominacion')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('tipo', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('serie', 100)->nullable();
            $table->string('dimensiones', 100)->nullable();
            $table->date('fecha_censo')->nullable();
            $table->integer('anio_auditoria')->nullable();
            $table->string('estado', 50)->nullable();
            $table->string('constatado_actual', 50)->nullable();
            $table->string('alerta_movimiento', 50)->nullable();
            $table->string('ubicacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('dni_auditor', 20)->nullable();
            $table->timestamp('creado_en')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_staging_censo');
    }
};
