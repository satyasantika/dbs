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
        // ADMIN
        Schema::create('exam_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('active')->default(0);
            $table->timestamps();
        });
        // ADMIN
        Schema::create('exam_form_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained();
            $table->integer('item_order');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('active')->default(0);
            $table->timestamps();
        });

        // MAHASISWA + DBS
        Schema::create('exam_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained();
            $table->integer('registration_order'); // ujian ke-...
            $table->foreignId('user_id')->constrained(); //mahasiswa
            $table->bigInteger('examiner1_id')->nullable()->unsigned();
            $table->bigInteger('examiner2_id')->nullable()->unsigned();
            $table->bigInteger('examiner3_id')->nullable()->unsigned();
            $table->bigInteger('guide1_id')->nullable()->unsigned();
            $table->bigInteger('guide2_id')->nullable()->unsigned();
            $table->bigInteger('chief')->nullable()->unsigned(); // ketua penguji?
            $table->date('exam_date')->nullable();
            $table->time('exam_time')->nullable();
            $table->text('title')->nullable();
            $table->double('ipk')->nullable();
            $table->string('room')->nullable();
            $table->string('online_link')->nullable();
            $table->string('online_user')->nullable();
            $table->string('online_password')->nullable();
            $table->string('schedule_link')->nullable();
            $table->boolean('pass_exam')->default(0);
            $table->timestamps();
        });
        
        // DOSEN
        Schema::create('exam_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_registration_id')->constrained();
            $table->foreignId('user_id')->constrained(); // dosen penguji
            $table->integer('examiner_order'); // urutan penguji
            $table->integer('score01')->nullable();
            $table->integer('score02')->nullable();
            $table->integer('score03')->nullable();
            $table->integer('score04')->nullable();
            $table->integer('score05')->nullable();
            $table->boolean('revision')->nullable(); // perlu revisi?
            $table->text('revision_note')->nullable();
            $table->boolean('pass_approved')->nullable(); // disetujui or layak or lulus?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_scores');
        Schema::dropIfExists('exam_registrations');
        Schema::dropIfExists('exam_form_items');
        Schema::dropIfExists('exam_types');
    }
};
