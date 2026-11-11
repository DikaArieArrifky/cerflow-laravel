<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('map_id')->nullable()->constrained('cer_maps')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('user_sessions')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->longText('map_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
