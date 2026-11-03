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
        Schema::create('bd_ubicaciones', function (Blueprint $table) {
            $table->increments('ubi_id');
            $table->string('ubi_codigo', 50)->nullable()->unique('bd_ubicaciones_ubi_codigo_key');
            $table->string('ubi_nombre')->unique('bd_ubicaciones_ubi_nombre_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_ubicaciones');
    }
};
