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
        Schema::create('bd_usuarios', function (Blueprint $table) {
            $table->increments('usu_id');
            $table->string('usu_dni', 20)->unique('bd_usuarios_usu_dni_key');
            $table->string('usu_nombres', 100);
            $table->string('usu_apellidos', 100);
            $table->string('usu_correo', 150)->unique('bd_usuarios_usu_correo_key');
            $table->string('password');
            $table->integer('rol_id');
            $table->smallInteger('usu_activo')->nullable()->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_usuarios');
    }
};
