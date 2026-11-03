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
        Schema::create('bd_censo_campo', function (Blueprint $table) {
            $table->bigIncrements('cdc_id');
            $table->bigInteger('cer_id');
            $table->bigInteger('act_id');
            $table->integer('est_id')->nullable();
            $table->integer('ubi_id')->nullable();
            $table->string('cdk_estado_limpieza', 50)->nullable();
            $table->string('cdk_etiqueta_estado', 50)->nullable();

            $table->unique(['cer_id', 'act_id'], 'bd_censo_campo_cer_id_act_id_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_censo_campo');
    }
};
