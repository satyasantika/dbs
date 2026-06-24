<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('year_generation');
            $table->foreignId('parent_submission_id')
                ->nullable()
                ->constrained('nuir_submissions');
            $table->tinyInteger('version')->default(1);
            $table->text('title');
            $table->longText('novelty')->nullable();
            $table->longText('urgency')->nullable();
            $table->longText('impact')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('dbs_reviewer_id')
                ->nullable()
                ->constrained('users');
            $table->text('dbs_note')->nullable();
            $table->timestamp('dbs_reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_submissions');
    }
};
