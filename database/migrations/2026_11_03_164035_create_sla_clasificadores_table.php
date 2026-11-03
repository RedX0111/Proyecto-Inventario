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
        Schema::create('sla_clasificadores', function (Blueprint $table) {
            $table->increments('cla_id');
            $table->string('cla_descripcion')->unique('sla_clasificadores_cla_descripcion_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_clasificadores');
    }
};
