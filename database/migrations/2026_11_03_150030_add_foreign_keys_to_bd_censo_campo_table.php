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
        Schema::table('bd_censo_campo', function (Blueprint $table) {
            $table->foreign(['act_id'], 'bd_censo_campo_act_id_fkey')->references(['act_id'])->on('bd_activos_maestro')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['cer_id'], 'bd_censo_campo_cer_id_fkey')->references(['cer_id'])->on('cen_censos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['est_id'], 'bd_censo_campo_est_id_fkey')->references(['est_id'])->on('bd_estados_fisicos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['ubi_id'], 'bd_censo_campo_ubi_id_fkey')->references(['ubi_id'])->on('bd_ubicaciones')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bd_censo_campo', function (Blueprint $table) {
            $table->dropForeign('bd_censo_campo_act_id_fkey');
            $table->dropForeign('bd_censo_campo_cer_id_fkey');
            $table->dropForeign('bd_censo_campo_est_id_fkey');
            $table->dropForeign('bd_censo_campo_ubi_id_fkey');
        });
    }
};
