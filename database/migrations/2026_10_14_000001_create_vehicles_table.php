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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('plate_number', 20)->unique();
            $table->string('brand', 60);
            $table->string('model', 100);
            $table->integer('year');
            $table->string('vin_chassis', 50)->unique()->nullable();
            $table->string('engine_number', 50)->nullable();
            $table->integer('engine_displacement_cc')->default(150);
            $table->string('cooling_type', 30)->default('liquid'); // 'liquid', 'air'
            $table->double('fuel_capacity_liter')->default(6.5);
            $table->double('current_odometer_km')->default(0.0);
            $table->string('telematics_unit_id', 64)->unique()->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();

            $table->index('plate_number', 'idx_vehicle_plate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
