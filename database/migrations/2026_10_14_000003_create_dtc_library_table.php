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
        Schema::create('dtc_library', function (Blueprint $table) {
            $table->id();
            $table->string('code', 15)->unique();
            $table->string('category', 50);
            $table->string('title_id', 150);
            $table->string('title_en', 150);
            $table->string('severity', 20)->default('warning'); // 'info', 'warning', 'critical'
            $table->text('description_id');
            $table->text('recommendation_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtc_library');
    }
};
