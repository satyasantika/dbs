<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nuir_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_words_novelty')->nullable()->after('max_chars_impact');
            $table->unsignedSmallInteger('max_words_novelty')->nullable()->after('min_words_novelty');
            $table->unsignedSmallInteger('min_words_urgency')->nullable()->after('max_words_novelty');
            $table->unsignedSmallInteger('max_words_urgency')->nullable()->after('min_words_urgency');
            $table->unsignedSmallInteger('min_words_impact')->nullable()->after('max_words_urgency');
            $table->unsignedSmallInteger('max_words_impact')->nullable()->after('min_words_impact');
            $table->unsignedTinyInteger('max_references')->default(10)->after('min_references_approved');
        });
    }

    public function down(): void
    {
        Schema::table('nuir_settings', function (Blueprint $table) {
            $table->dropColumn([
                'min_words_novelty',
                'max_words_novelty',
                'min_words_urgency',
                'max_words_urgency',
                'min_words_impact',
                'max_words_impact',
                'max_references',
            ]);
        });
    }
};
