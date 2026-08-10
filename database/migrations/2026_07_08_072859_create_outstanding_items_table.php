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
        Schema::create('outstanding_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->string('unit'); // Farmasi, Laboratorium, Radiologi, Bedside, Admin OK, Tindakan
            $table->string('category');
            $table->string('item_name');
            $table->decimal('price', 15, 2)->default(0);
            $table->date('tgl_tindakan');
            $table->date('tgl_input_sistem')->nullable();
            $table->string('status')->default('pending'); // pending, completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outstanding_items');
    }
};
