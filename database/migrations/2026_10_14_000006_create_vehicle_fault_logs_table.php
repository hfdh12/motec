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
        Schema::create('vehicle_fault_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('dtc_code', 15);
            $table->foreignId('session_id')->nullable()->constrained('diagnostic_sessions')->onDelete('set null');
            $table->timestamp('detected_at')->nullable()->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->timestamp('cleared_at')->nullable();
            $table->string('cleared_by', 60)->nullable();
            $table->timestamps();

            $table->foreign('dtc_code')->references('code')->on('dtc_library')->onDelete('cascade');
            $table->index(['vehicle_id', 'is_active'], 'idx_faults_vehicle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_fault_logs');
    }
};
