<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cer_maps', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->boolean('allow_feedback')->default(true);
            $table->text('reading_text')->nullable();
            $table->boolean('allow_reading')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cer_maps');
    }
};
