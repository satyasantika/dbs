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
        // MAHASISWA > DOSEN
        // Tahap pemilihan pembimbing 1/2/3
        Schema::create('selection_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); //mahasiswa
            $table->integer('stage_order')->nullable();
            $table->bigInteger('guide1_id')->nullable()->unsigned();
            $table->bigInteger('guide2_id')->nullable()->unsigned();
            $table->boolean('final')->default(0);// final?
            $table->bigInteger('examiner1_id')->nullable()->unsigned();
            $table->bigInteger('examiner2_id')->nullable()->unsigned();
            $table->bigInteger('examiner3_id')->nullable()->unsigned();
            $table->timestamps();
        });
        // MAHASISWA > DBS | DOSEN
        // Tahap pengajuan NUIR
        Schema::create('selection_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_stage_id')->constrained();
            $table->integer('revision_order')->nullable();
            $table->string('element')->nullable(); // title, novelty, urgency, impact, references
            $table->longText('description')->nullable();
            $table->string('link')->nullable();
            $table->boolean('approved')->default(0); //disetujui?
            $table->timestamps();
        });

        // MAHASISWA > DBS | DOSEN
        // Verifikasi pengajuan elemen NUIR
        Schema::create('selection_element_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_element_id')->constrained();
            $table->foreignId('user_id')->constrained(); //dosen
            $table->string('verificator')->nullable(); //dosen or dbs
            $table->longText('comment')->nullable();
            $table->boolean('revised')->nullable(); //direvisi?
            $table->timestamps();
        });

        // MAHASISWA > DOSEN
        // Pengajuan Pembimbing pada tahap 1/2/3
        Schema::create('selection_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_stage_id')->constrained();
            $table->foreignId('user_id')->constrained(); //dosen
            $table->integer('guide_order')->nullable();
            $table->boolean('approved')->nullable(); //disetujui?
            $table->timestamps();
        });

        // DBS
        // Set kuota pembimbing dan penguji
        Schema::create('selection_guide_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); //dosen
            $table->integer('year')->nullable();
            $table->string('position')->nullable(); // pembimbing or penguji
            $table->integer('position_order')->nullable();
            $table->integer('quota')->nullable();
            $table->boolean('final')->nullable();// final?
            $table->timestamps();
        });

        // DBS
        // Set kuota pembimbing dan penguji
        Schema::create('selection_guide_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_guide_allocation_id')->constrained(); //dosen
            $table->integer('group')->nullable();
            $table->integer('quota')->nullable();
            $table->boolean('final')->nullable();// final?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selection_guide_groups');
        Schema::dropIfExists('selection_guide_allocations');
        Schema::dropIfExists('selection_guides');
        Schema::dropIfExists('selection_element_comments');
        Schema::dropIfExists('selection_elements');
        Schema::dropIfExists('selection_stages');
    }
};
