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
        Schema::table('aud_bitacora_trazabilidad', function (Blueprint $table) {
            $table->foreign(['act_id'], 'aud_bitacora_trazabilidad_act_id_fkey')->references(['act_id'])->on('bd_activos_maestro')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['usu_id'], 'aud_bitacora_trazabilidad_usu_id_fkey')->references(['usu_id'])->on('bd_usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aud_bitacora_trazabilidad', function (Blueprint $table) {
            $table->dropForeign('aud_bitacora_trazabilidad_act_id_fkey');
            $table->dropForeign('aud_bitacora_trazabilidad_usu_id_fkey');
        });
    }
};
