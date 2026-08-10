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
        Schema::create('discharge_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->string('lab_status')->default('none'); // done, pending, none
            $table->string('lab_detail')->nullable();
            $table->string('bedside_status')->default('none'); // done, pending, none
            $table->string('admin_ok_status')->default('none'); // done, pending, none
            $table->string('farmasi_status')->default('none'); // done, pending, error
            $table->string('radiologi_status')->default('none'); // done, pending, none
            $table->string('cot_status')->default('TIDAK'); // YA, TIDAK
            $table->decimal('operator_fee', 15, 2)->default(0);
            $table->decimal('anestesi_fee', 15, 2)->default(0);
            $table->text('keterangan_proses')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discharge_checklists');
    }
};
