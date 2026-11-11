<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('map_id')->constrained('cer_maps')->cascadeOnDelete();
            $table->timestamp('start_time')->useCurrent();
            $table->boolean('is_submitted')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
