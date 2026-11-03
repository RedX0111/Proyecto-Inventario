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
        Schema::create('cen_censos', function (Blueprint $table) {
            $table->bigIncrements('cer_id');
            $table->integer('per_id');
            $table->date('cer_fecha');
            $table->text('cer_observaciones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cen_censos');
    }
};
