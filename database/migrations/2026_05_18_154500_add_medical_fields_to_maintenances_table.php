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
        Schema::table('maintenances', function (Blueprint $table) {
            $table->string('diagnosa_gejala')->nullable();
            $table->string('lokasi_rawat')->nullable();
            $table->string('kondisi_klinis')->nullable();
            $table->string('metode_pembayaran')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn(['diagnosa_gejala', 'lokasi_rawat', 'kondisi_klinis', 'metode_pembayaran']);
        });
    }
};
