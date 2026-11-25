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
        Schema::create('telematics_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->string('device_uid', 64)->unique();
            $table->string('device_model', 60)->default('MOTEC-IoT-ESP32-V2');
            $table->string('firmware_version', 30)->default('v2.4.1');
            $table->string('protocol', 30)->default('MQTT/HTTP'); // 'CAN-BUS', 'K-LINE', 'OBD2-ELM327', 'BLE'
            $table->string('api_key', 100);
            $table->double('battery_backup_v')->default(3.7);
            $table->string('status', 20)->default('active'); // 'active', 'standby', 'offline'
            $table->timestamp('last_seen')->nullable()->useCurrent();
            $table->timestamps();

            $table->index('device_uid', 'idx_device_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telematics_devices');
    }
};
