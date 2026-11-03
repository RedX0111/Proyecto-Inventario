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
        Schema::table('bd_activos_maestro', function (Blueprint $table) {
            $table->foreign(['cla_id'], 'bd_activos_maestro_cla_id_fkey')->references(['cla_id'])->on('sla_clasificadores')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['col_id'], 'bd_activos_maestro_col_id_fkey')->references(['col_id'])->on('col_colores')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['mod_id'], 'bd_activos_maestro_mod_id_fkey')->references(['mod_id'])->on('mod_modelos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bd_activos_maestro', function (Blueprint $table) {
            $table->dropForeign('bd_activos_maestro_cla_id_fkey');
            $table->dropForeign('bd_activos_maestro_col_id_fkey');
            $table->dropForeign('bd_activos_maestro_mod_id_fkey');
        });
    }
};
