<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuir_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nuir_submission_id')->constrained();
            $table->tinyInteger('ref_order');
            $table->string('link_ojs')->nullable();
            $table->string('indexer_name')->nullable();
            $table->string('link_index')->nullable();
            $table->string('link_drive')->nullable();
            $table->text('quote')->nullable();
            $table->text('relevance')->nullable();
            $table->boolean('ref_approved')->nullable();
            $table->text('ref_note')->nullable();
            $table->timestamps();
            $table->unique(['nuir_submission_id', 'ref_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuir_references');
    }
};
