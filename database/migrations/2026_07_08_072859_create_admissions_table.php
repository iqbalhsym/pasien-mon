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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('rm')->index();
            $table->string('name');
            $table->string('insurance');
            $table->string('room');
            $table->string('floor');
            $table->string('dpjp');
            $table->date('tgl_rencana_pulang');
            $table->time('jam_rencana_pulang')->nullable();
            $table->dateTime('tgl_aktual_pulang')->nullable();
            $table->string('status_akhir')->default('Open'); // Open, Tunda Pulang, Selesai, Meninggal, Rujuk
            $table->integer('length_of_stay')->default(0);
            $table->decimal('total_bill', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
