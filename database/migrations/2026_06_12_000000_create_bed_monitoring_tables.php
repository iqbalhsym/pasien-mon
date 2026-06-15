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
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('wings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained('floors')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->unique(['floor_id', 'name']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // uses room_id from API
            $table->foreignId('wing_id')->constrained('wings')->onDelete('cascade');
            $table->string('name');
            $table->string('class')->nullable();
            $table->integer('total_beds')->default(0);
            $table->timestamps();
        });

        Schema::create('beds', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // uses bed_id from API
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->string('bed_number');
            $table->string('status')->default('kosong');
            $table->boolean('is_active')->default(true);
            $table->foreignId('equipment_id')->nullable()->constrained('equipments')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beds');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('wings');
        Schema::dropIfExists('floors');
    }
};
