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
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->string('no_pengajuan')->unique();
            $table->string('nama_pengadaan');
            $table->string('unit_pemohon');
            $table->string('jenis_barang_jasa');
            $table->string('status_kak')->default('Pending');
            $table->string('status_sph')->default('Pending');
            $table->string('status_andieni')->default('Pending');
            $table->date('tanggal_pengajuan');
            $table->date('target_selesai');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};
