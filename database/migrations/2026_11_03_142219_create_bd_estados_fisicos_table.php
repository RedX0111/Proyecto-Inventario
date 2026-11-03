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
        Schema::create('bd_estados_fisicos', function (Blueprint $table) {
            $table->increments('est_id');
            $table->string('est_nombre', 100)->unique('bd_estados_fisicos_est_nombre_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_estados_fisicos');
    }
};
