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
        Schema::table('mod_modelos', function (Blueprint $table) {
            $table->foreign(['mar_id'], 'mod_modelos_mar_id_fkey')->references(['mar_id'])->on('mar_marcas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mod_modelos', function (Blueprint $table) {
            $table->dropForeign('mod_modelos_mar_id_fkey');
        });
    }
};
