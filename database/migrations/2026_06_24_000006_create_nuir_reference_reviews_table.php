<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_reference_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nuir_reference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('role');
            $table->boolean('approved')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['nuir_reference_id', 'user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_reference_reviews');
    }
};
