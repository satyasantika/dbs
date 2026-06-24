<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nuir_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('validator_id')->constrained('users');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('assigned_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_assignments');
    }
};
