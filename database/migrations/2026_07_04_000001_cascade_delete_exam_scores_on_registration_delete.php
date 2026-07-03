<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropForeign(['exam_registration_id']);
        });

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->foreign('exam_registration_id')
                ->references('id')->on('exam_registrations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropForeign(['exam_registration_id']);
        });

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->foreign('exam_registration_id')
                ->references('id')->on('exam_registrations');
        });
    }
};
