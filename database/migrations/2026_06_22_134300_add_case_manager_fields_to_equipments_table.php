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
            $table->bigInteger('billing_aktual')->nullable();
            $table->bigInteger('pagu_budget')->nullable();
            $table->string('persentase_pagu')->nullable();
            $table->string('kategori_pasien')->nullable();
            $table->string('target_los')->nullable();
            $table->text('notes_num')->nullable();
            $table->text('notes_case_manager')->nullable();
            $table->text('riw_lab')->nullable();
            $table->text('riw_rad')->nullable();
            $table->text('riw_obat')->nullable();
            $table->text('rencana_prosedur')->nullable();
            $table->text('rencana_diagnostik')->nullable();
            $table->text('rencana_konsul')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn([
                'billing_aktual', 'pagu_budget', 'persentase_pagu', 'kategori_pasien',
                'target_los', 'notes_num', 'notes_case_manager', 'riw_lab', 'riw_rad',
                'riw_obat', 'rencana_prosedur', 'rencana_diagnostik', 'rencana_konsul'
            ]);
        });
    }
};
