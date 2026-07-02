<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nuir_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_words_title')->nullable()->after('max_words_impact');
            $table->unsignedSmallInteger('max_words_title')->nullable()->after('min_words_title');
        });

        Schema::table('nuir_proposals', function (Blueprint $table) {
            $table->dropForeign(['guide1_id']);
            $table->dropForeign(['guide2_id']);
        });

        Schema::table('nuir_proposals', function (Blueprint $table) {
            $table->foreignId('guide1_id')->nullable()->change();
            $table->foreignId('guide2_id')->nullable()->change();
            $table->foreign('guide1_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('guide2_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nuir_proposals', function (Blueprint $table) {
            $table->dropForeign(['guide1_id']);
            $table->dropForeign(['guide2_id']);
        });

        Schema::table('nuir_proposals', function (Blueprint $table) {
            $table->foreignId('guide1_id')->nullable(false)->change();
            $table->foreignId('guide2_id')->nullable(false)->change();
            $table->foreign('guide1_id')->references('id')->on('users');
            $table->foreign('guide2_id')->references('id')->on('users');
        });

        Schema::table('nuir_settings', function (Blueprint $table) {
            $table->dropColumn(['min_words_title', 'max_words_title']);
        });
    }
};
