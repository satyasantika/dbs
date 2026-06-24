<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nuir_submission_id')->constrained();
            $table->foreignId('guide1_id')->constrained('users');
            $table->foreignId('guide2_id')->constrained('users');
            $table->string('guide1_status')->default('pending');
            $table->string('guide2_status')->default('pending');
            $table->text('guide1_note')->nullable();
            $table->text('guide2_note')->nullable();
            $table->timestamp('guide1_responded_at')->nullable();
            $table->timestamp('guide2_responded_at')->nullable();
            $table->boolean('final')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_proposals');
    }
};
