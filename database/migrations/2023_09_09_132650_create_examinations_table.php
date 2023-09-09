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
        Schema::create('exam_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_form_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained();
            $table->integer('order');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(0);
            $table->timestamps();
        });
        Schema::create('exam_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); //mahasiswa
            $table->foreignId('exam_type_id')->constrained();
            $table->integer('order'); // ujian ke-...
            $table->text('title');
            $table->double('ipk');
            $table->string('room');
            $table->date('exam_date');
            $table->string('exam_time');
            $table->string('online_link');
            $table->string('online_user');
            $table->string('online_password');
            $table->string('schedule_link');
            $table->timestamps();
        });
        Schema::create('exam_examiners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_registration_id')->constrained();
            $table->foreignId('user_id')->constrained(); // penguji
            $table->integer('user_order'); // urutan penguji
            $table->boolean('is_chief'); // ketua penguji?
            $table->text('note')->nullable();
            $table->boolean('need_revision')->nullable(); // perlu revisi?
            $table->boolean('is_approved')->nullable(); // disetujui or layak or lulus?
            $table->timestamps();
        });
        Schema::create('exam_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_examiner_id')->constrained();
            $table->foreignId('exam_form_item_id')->constrained();
            $table->integer('score')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_scores');
        Schema::dropIfExists('exam_examiners');
        Schema::dropIfExists('exam_registrations');
        Schema::dropIfExists('exam_form_items');
        Schema::dropIfExists('exam_types');
    }
};
