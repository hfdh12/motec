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
        Schema::create('telemetry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->timestamp('timestamp')->nullable()->useCurrent();

            // Parameter Mesin & Performa
            $table->integer('engine_rpm')->default(0);
            $table->double('speed_kmh')->default(0.0);
            $table->double('engine_temp_c')->default(0.0);
            $table->double('tps_throttle_percent')->default(0.0);
            $table->integer('gear_position')->default(0);

            // Parameter Kelistrikan
            $table->double('battery_voltage_v')->default(12.6);
            $table->double('stator_charging_amp')->default(0.0);

            // Parameter Pelumasan & Sensor
            $table->double('oil_health_percent')->default(100.0);
            $table->double('oil_pressure_kpa')->default(250.0);
            $table->double('o2_sensor_v')->default(0.45);
            $table->double('map_sensor_kpa')->default(101.3);

            // Parameter Keamanan & Fisik
            $table->double('front_tire_psi')->default(29.0);
            $table->double('rear_tire_psi')->default(33.0);
            $table->double('brake_pad_front_percent')->default(95.0);
            $table->double('brake_pad_rear_percent')->default(95.0);
            $table->double('fuel_level_percent')->default(80.0);

            // Kondisi Lingkungan & Status
            $table->double('ambient_temp_c')->default(30.0);
            $table->boolean('sidestand_down')->default(false);
            $table->double('lean_angle_deg')->default(0.0);
            $table->double('odometer_km');
            $table->longText('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'timestamp'], 'idx_telemetry_vehicle_ts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_logs');
    }
};
