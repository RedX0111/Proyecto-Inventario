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
        Schema::table('bd_usuarios', function (Blueprint $table) {
            $table->foreign(['rol_id'], 'bd_usuarios_rol_id_fkey')->references(['rol_id'])->on('roles')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bd_usuarios', function (Blueprint $table) {
            $table->dropForeign('bd_usuarios_rol_id_fkey');
        });
    }
};
