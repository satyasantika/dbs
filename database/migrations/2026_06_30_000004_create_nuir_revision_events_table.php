<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_revision_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nuir_submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('submission_version')->default(1);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role');
            $table->string('event_type');
            $table->string('subject');
            $table->unsignedTinyInteger('ref_order')->nullable();
            $table->foreignId('nuir_proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['nuir_submission_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_revision_events');
    }
};
