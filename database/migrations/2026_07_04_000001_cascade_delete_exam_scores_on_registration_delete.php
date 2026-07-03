<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->foreignKeyExists('exam_scores_exam_registration_id_foreign')) {
            Schema::table('exam_scores', function (Blueprint $table) {
                $table->dropForeign(['exam_registration_id']);
            });
        }

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->foreign('exam_registration_id')
                ->references('id')->on('exam_registrations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if ($this->foreignKeyExists('exam_scores_exam_registration_id_foreign')) {
            Schema::table('exam_scores', function (Blueprint $table) {
                $table->dropForeign(['exam_registration_id']);
            });
        }

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->foreign('exam_registration_id')
                ->references('id')->on('exam_registrations');
        });
    }

    /**
     * Production's exam_scores table drifted from what earlier migrations describe —
     * this named constraint doesn't always exist there, so dropForeign() errors
     * (1091) instead of no-op'ing. Check information_schema first.
     */
    private function foreignKeyExists(string $constraintName): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'exam_scores')
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
