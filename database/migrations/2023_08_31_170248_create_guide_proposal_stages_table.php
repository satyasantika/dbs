<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proposal_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); //mahasiswa
            $table->integer('stage')->nullable();
            $table->bigInteger('guide1_id')->nullable()->unsigned();
            $table->bigInteger('guide2_id')->nullable()->unsigned();
            $table->boolean('is_final')->default(0);
            $table->timestamps();
        });
        Schema::create('proposal_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_stage_id')->constrained();
            $table->integer('revision')->nullable();
            $table->string('element')->nullable(); // title, novelty, urgency, impact, references
            $table->longText('description')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_approved')->default(0);
            $table->timestamps();
        });
        Schema::create('proposal_step_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_step_id')->constrained();
            $table->foreignId('user_id')->constrained(); //dosen
            $table->string('verificator')->nullable(); //dosen or dbs
            $table->longText('comment')->nullable();
            $table->boolean('need_revision')->nullable();
            $table->timestamps();
        });
        Schema::create('proposal_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_stage_id')->constrained();
            $table->foreignId('user_id')->constrained(); //dosen
            $table->integer('order')->nullable();
            $table->boolean('is_accepted')->nullable();
            $table->timestamps();
        });
        Schema::create('guide_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); //dosen
            $table->integer('year')->nullable();
            $table->string('position')->nullable(); // pembimbing or penguji
            $table->integer('order')->nullable();
            $table->integer('quota')->nullable();
            $table->boolean('is_final')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_allocations');
        Schema::dropIfExists('proposal_guides');
        Schema::dropIfExists('proposal_step_comments');
        Schema::dropIfExists('proposal_steps');
        Schema::dropIfExists('proposal_stages');
    }
};
