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
        Schema::create('mar_marcas', function (Blueprint $table) {
            $table->increments('mar_id');
            $table->string('mar_nombre', 100)->unique('mar_marcas_mar_nombre_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mar_marcas');
    }
};
