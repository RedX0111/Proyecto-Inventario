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
        Schema::create('bd_activos_maestro', function (Blueprint $table) {
            $table->bigIncrements('act_id');
            $table->integer('act_orden')->nullable();
            $table->string('act_codigo', 50)->unique('bd_activos_maestro_act_codigo_key');
            $table->string('act_denominacion');
            $table->integer('mod_id')->nullable();
            $table->integer('col_id')->nullable();
            $table->integer('cla_id')->nullable();
            $table->string('act_serie', 100)->nullable();
            $table->string('act_dimensiones', 100)->nullable();
            $table->smallInteger('act_es_temporal')->nullable()->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_activos_maestro');
    }
};
