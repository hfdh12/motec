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
        Schema::create('diagnostic_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('session_uid', 64)->unique();
            $table->integer('total_score');
            $table->integer('engine_score');
            $table->integer('electrical_score');
            $table->integer('cooling_score');
            $table->integer('lubrication_score');
            $table->integer('safety_score');
            $table->string('status', 30); // 'EXCELLENT', 'GOOD', 'ATTENTION_NEEDED', 'CRITICAL'
            $table->integer('detected_dtc_count')->default(0);
            $table->text('findings_summary')->nullable();
            $table->text('action_items')->nullable();
            $table->string('tested_by', 50)->default('motec-ai-scanner');
            $table->timestamps();

            $table->index(['vehicle_id', 'created_at'], 'idx_diag_vehicle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_sessions');
    }
};
