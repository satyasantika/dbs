<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_content_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nuir_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->string('field');
            $table->boolean('approved');
            $table->text('note')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->unique(['nuir_submission_id', 'user_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_content_reviews');
    }
};
