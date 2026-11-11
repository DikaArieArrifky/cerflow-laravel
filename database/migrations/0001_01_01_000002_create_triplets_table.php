<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triplets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('cer_maps')->cascadeOnDelete();
            $table->text('claim')->nullable();
            $table->text('evidence')->nullable();
            $table->text('reasoning')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triplets');
    }
};
