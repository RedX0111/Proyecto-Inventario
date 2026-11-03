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
        Schema::table('cen_censos', function (Blueprint $table) {
            $table->foreign(['per_id'], 'cen_censos_per_id_fkey')->references(['per_id'])->on('bd_periodos_fiscales')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cen_censos', function (Blueprint $table) {
            $table->dropForeign('cen_censos_per_id_fkey');
        });
    }
};
