<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_test_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cer_map_id')->constrained('cer_maps')->cascadeOnDelete();
            $table->decimal('pre_test', 5, 2)->nullable();
            $table->decimal('post_test', 5, 2)->nullable();
            $table->decimal('delayed_test', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'cer_map_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_test_scores');
    }
};

