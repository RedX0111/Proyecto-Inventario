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
        Schema::table('bd_ubicaciones', function (Blueprint $table) {
            $table->foreign(['edi_id'], 'bd_ubicaciones_edi_id_fkey')->references(['edi_id'])->on('edi_edificios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['tip_id'], 'bd_ubicaciones_tip_id_fkey')->references(['tip_id'])->on('tip_tipos_ambiente')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bd_ubicaciones', function (Blueprint $table) {
            $table->dropForeign('bd_ubicaciones_edi_id_fkey');
            $table->dropForeign('bd_ubicaciones_tip_id_fkey');
        });
    }
};
