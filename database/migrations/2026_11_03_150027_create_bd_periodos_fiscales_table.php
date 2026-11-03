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
        Schema::create('bd_periodos_fiscales', function (Blueprint $table) {
            $table->increments('per_id');
            $table->integer('per_anio')->unique('bd_periodos_fiscales_per_anio_key');
            $table->date('per_fecha_inicio')->nullable();
            $table->date('per_fecha_cierre')->nullable();
            $table->smallInteger('per_cerrado')->nullable()->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_periodos_fiscales');
    }
};
