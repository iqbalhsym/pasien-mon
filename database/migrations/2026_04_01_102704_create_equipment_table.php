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
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->string('merk');
            $table->string('type');
            $table->string('serial_number')->unique();
            $table->string('lokasi');
            $table->string('kondisi')->default('Baik');
            $table->text('spesifikasi')->nullable();
            $table->date('tanggal_pengadaan');
            $table->string('gambar')->nullable();
            $table->string('status_kepemilikan')->default('Milik RS');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
