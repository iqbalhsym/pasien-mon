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
        Schema::table('equipments', function (Blueprint $table) {
            $table->string('registered_date')->nullable();
            $table->string('los_aktual')->nullable();
            $table->string('dpjp_utama')->nullable();
            $table->string('dpjp_raber')->nullable();
            $table->string('dokter_konsul')->nullable();
            $table->string('visit_dpjp')->nullable();
            $table->text('planning_pasien')->nullable();
            $table->text('rencana_pulang')->nullable();
            $table->string('npja')->nullable();
            $table->string('ews')->nullable();
            $table->string('tingkat_ketergantungan')->nullable();
            $table->string('ners_bertugas')->nullable();
            $table->text('alkes_invasif')->nullable();
            $table->text('tindakan_detail')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn([
                'registered_date',
                'los_aktual',
                'dpjp_utama',
                'dpjp_raber',
                'dokter_konsul',
                'visit_dpjp',
                'planning_pasien',
                'rencana_pulang',
                'npja',
                'ews',
                'tingkat_ketergantungan',
                'ners_bertugas',
                'alkes_invasif',
                'tindakan_detail'
            ]);
        });
    }
};
