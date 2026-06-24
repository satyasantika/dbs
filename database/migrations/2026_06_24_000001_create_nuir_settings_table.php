<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_settings', function (Blueprint $table) {
            $table->id();
            $table->string('year_generation');
            $table->tinyInteger('stage');
            $table->boolean('active')->default(false);
            $table->date('deadline')->nullable();
            $table->tinyInteger('min_references_approved')->default(10);
            $table->integer('max_chars_novelty')->nullable();
            $table->integer('max_chars_urgency')->nullable();
            $table->integer('max_chars_impact')->nullable();
            $table->timestamps();
            $table->unique('year_generation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_settings');
    }
};
