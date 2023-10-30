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
        // DBS
        // Set kuota pembimbing dan penguji
        Schema::create('guide_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); //dosen
            $table->integer('year')->nullable();
            $table->integer('guide1_quota')->default(0);
            $table->integer('guide2_quota')->default(0);
            $table->integer('examiner_quota')->default(0);
            $table->boolean('active')->default(0);// aktif?
            $table->timestamps();
        });

        // DBS
        // Set kuota pembimbing dan penguji
        Schema::create('guide_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_allocation_id')->constrained(); //dosen
            $table->integer('group')->nullable();
            $table->integer('guide1_quota')->default(0);
            $table->integer('guide2_quota')->default(0);
            $table->boolean('active')->default(0);// aktif?
            $table->timestamps();
        });

        // MAHASISWA > DOSEN
        // Tahap pemilihan pembimbing 1/2/3
        Schema::create('selection_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); //mahasiswa
            $table->integer('stage_order');
            $table->bigInteger('guide1_id')->nullable()->unsigned();
            $table->bigInteger('guide2_id')->nullable()->unsigned();
            $table->bigInteger('examiner1_id')->nullable()->unsigned();
            $table->bigInteger('examiner2_id')->nullable()->unsigned();
            $table->bigInteger('examiner3_id')->nullable()->unsigned();
            $table->boolean('final')->default(0);// final?
            $table->timestamps();
        });
        // MAHASISWA > DBS | DOSEN
        // Tahap pengajuan NUIR
        Schema::create('selection_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_stage_id')->constrained();
            $table->bigInteger('parent_id')->nullable()->unsigned();
            $table->string('element'); // title, novelty, urgency, impact, references
            $table->longText('description')->nullable();
            $table->string('link')->nullable();
            $table->boolean('approved')->nullable(); //disetujui?
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
            $table->foreignId('guide_group_id')->nullable()->constrained();//kelompok
            $table->integer('pair_order')->nullable();//urutan pasangan pembimbing
            $table->foreignId('user_id')->nullable()->constrained();//dosen
            $table->integer('guide_order')->nullable();
            $table->boolean('approved')->nullable(); //disetujui?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selection_guides');
        Schema::dropIfExists('selection_element_comments');
        Schema::dropIfExists('selection_elements');
        Schema::dropIfExists('selection_stages');
        Schema::dropIfExists('guide_groups');
        Schema::dropIfExists('guide_allocations');
    }
};
